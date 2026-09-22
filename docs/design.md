# JobTrail — Design Document (Phase 1)

- **Date:** 2026-09-15
- **Status:** Accepted
- **Scope:** Phase 1 covers manual job tracking. The analytics dashboard and job-source connectors are designed in separate documents.

## 1. Overview

JobTrail is a personal, single-user job search tracker that runs locally. It keeps a database of job postings and the companies behind them. It tracks which postings were applied to, and records the full history of each application's status changes.

### Goals (Phase 1)

- Manually add job postings, including pasting the full ad text.
- Manage companies as first-class records.
- Tag postings with seniority and skills.
- Track applications with fixed statuses and a timestamped status-change history.
- See at a glance which postings were applied to and which were not.
- `docker compose up` brings up the whole app, including DB, migrations and demo data, with no manual steps.
- Automated tests against real PostgreSQL, plus CI on GitHub Actions.

### Non-goals (Phase 1)

- Authentication or multi-user support.
- Automatic job collection (Phase 2).
- Analytics dashboard (Phase 1.5).
- Contacts (recruiters, hiring managers), kanban view, browser tests, production deployment, Redis.

### Roadmap

| Phase | Content | Design |
|---|---|---|
| 1 | Postings, companies, skills, applications + history, Docker, tests, CI, demo seed | this document |
| 1.5 | Analytics dashboard: applications over time, response rates, time-to-response, most requested skills, per-source conversion | separate |
| 2 | Pluggable job-source connectors (API/RSS only, no scraping; German market first: Bundesagentur für Arbeit Jobsuche, Arbeitnow, Adzuna), enabled/disabled via config, scheduler + queue; a company's recorded ATS (§4.2) can point a connector to its public job feed | separate |
| later | Contacts, kanban view, browser tests | — |

### Phase 1 milestones

| # | Milestone | Delivers |
|---|---|---|
| 1 | Foundation | Project scaffold, Docker environment, Makefile, PostgreSQL test database, app layout, CI. `docker compose up` serves an empty app and all checks pass. |
| 2 | Domain | Migrations, enums, models, Actions, domain and constraint tests, factories, demo data |
| 3 | UI | Postings, Applications and Companies screens with feature tests |
| 4 | Polish | README, screenshots, CI badge |

## 2. Tech Stack

Versions as of September 2026.

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel 13 on PHP 8.4 | Laravel 13 supports PHP 8.3–8.5 |
| Frontend | Official Laravel Vue starter kit, blank variant (no authentication): Inertia 3, Vue 3 Composition API, TypeScript, Tailwind 4, Vite 8 + Vite+ | shadcn-vue components are added as needed; see §2.1 |
| Database | PostgreSQL 18 | Laravel 13 supports PostgreSQL 10.0+ |
| Tests | Pest 5 (feature tests first) | Run against PostgreSQL, never SQLite |
| Quality | Pint, Larastan (level 7), `vue-tsc`, Vite+ `vp check` (lint + format) | |
| Infra | Docker Compose, Makefile | |
| CI | GitHub Actions | |

### 2.1 Starting point: blank Vue kit + shadcn-vue

JobTrail is single-user and local, so it needs no authentication. The full starter kit ships with login, registration, 2FA, passkeys and settings pages, all of which would have to be deleted. Instead, the project starts from the kit's **blank** variant, so every file in the repository is there on purpose.

UI components come from shadcn-vue. They are added one at a time as screens need them, and live in `resources/js/components/ui/` as regular, editable source files.

The default `users` table migration is kept but unused.

## 3. Architecture

### 3.1 Docker Compose services

| Service | Image | Purpose |
|---|---|---|
| `app` | custom (`docker/app/Dockerfile`): `php:8.4-fpm` + `pdo_pgsql`, `intl`, Composer, **Node 24** | Laravel via PHP-FPM; runs the entrypoint |
| `web` | `nginx:alpine` | Serves `http://localhost:8080`; forwards PHP to `app:9000` |
| `vite` | same image as `app` | `npm run dev` with hot module replacement on port 5173 |
| `db` | `postgres:18` | Healthcheck (`pg_isready`); named volume `pgdata` mounted at **`/var/lib/postgresql`** |
| `pgadmin` | `dpage/pgadmin4` | `http://localhost:5050`, preconfigured server entry for `db` |

**Why `vite` reuses the PHP image:** the Wayfinder Vite plugin generates typed routes by running `php artisan wayfinder:generate`, so the Vite process needs PHP. One image with PHP and Node covers both.

**Volume path:** the `postgres:18` image moved `PGDATA` to a version-specific directory. Mounts must target `/var/lib/postgresql`, not the pre-18 `/var/lib/postgresql/data`, or data will not persist.

**Code:** the project root is bind-mounted into `app` and `vite` at `/var/www/html`. `vendor/` and `node_modules/` are installed by the containers. All `composer`/`npm`/`artisan` commands run inside containers (via `make`), so Linux-native binaries never conflict with the host.

**Vite in Docker:** the dev server listens on `0.0.0.0:5173` and advertises `localhost:5173` to the browser.

### 3.2 Startup flow (`docker compose up`)

1. `db` starts; `app` waits via `depends_on: condition: service_healthy`.
2. `docker/app/entrypoint.sh` in `app`:
   1. If the checkout belongs to a non-root user (a Linux host), `www-data` takes that user's uid and gid, and every following step runs as `www-data`, so PHP-FPM can write `storage/` and created files belong to the host user. Docker Desktop for macOS shows the checkout as root-owned, so nothing changes there.
   2. `composer install` if `vendor/` is missing.
   3. If `.env` is missing: copy `.env.example`, then `php artisan key:generate`.
   4. `php artisan migrate --force`. This applies only pending migrations and never drops data.
   5. `php artisan app:seed-demo-if-empty`. It seeds demo data only when the `companies` table is empty.
   6. `exec php-fpm`.
3. `vite` waits for `app` to be healthy and runs `docker/vite/start.sh` as the checkout's owner: `npm install` if `node_modules/` is missing, then `npm run dev`.
4. The app is available at `http://localhost:8080`.

The `app` healthcheck passes only after the entrypoint finishes, e.g. by checking a marker file that the entrypoint writes just before `exec php-fpm`.

### 3.3 Data persistence rules

| Action | Result |
|---|---|
| `docker compose stop`, Ctrl+C, `docker compose down` | Data kept |
| `docker compose up` again | Same data; only new migrations run; no re-seed |
| `make fresh` (`migrate:fresh --seed`) | **Explicit** reset to demo data |
| `docker compose down -v` | Volume deleted, so the DB starts from scratch |

Nothing destructive ever runs automatically.

### 3.4 Makefile

`up`, `down`, `fresh`, `test`, `lint`, `shell` (bash in `app`), `logs`, `composer`/`npm`/`artisan` passthroughs (e.g. `make artisan cmd="route:list"`). The passthroughs run as `www-data` (see §3.2), so their files belong to the checkout's owner.

### 3.5 Code layout

```
app/
  Enums/          ApplicationStatus, WorkMode, EmploymentType, Seniority, SalaryPeriod
  Models/         Company, JobPosting, JobApplication, JobApplicationEvent, Skill
  Actions/        CreateJobPosting, ChangeApplicationStatus, UpdateApplicationEvent,
                  DeleteLatestApplicationEvent, ...
  Http/Controllers/  thin; validate via FormRequests, delegate to Actions
  Http/Requests/
  Console/Commands/  SeedDemoIfEmpty
database/
  migrations/ factories/ seeders/ (DatabaseSeeder -> DemoSeeder)
docker/
  app/Dockerfile, app/entrypoint.sh, vite/start.sh, nginx/default.conf, postgres/init/01-create-testing-db.sql
resources/js/
  pages/ (postings/, applications/, companies/)  components/  layouts/  types/
```

`app/JobSources/` is **not** created in Phase 1. It belongs to the Phase 2 design.

## 4. Data Model

All timestamps use `timestamptz` and are stored in UTC. The UI renders them in the browser's local timezone.

Enum-like columns are `varchar` with a `CHECK` constraint. The allowed values come from PHP backed enums in `app/Enums`, which are the single source of truth.

### 4.1 Relationships

```
Company 1 ──< JobPosting >──< Skill            (job_posting_skill pivot)
                  │
                  1
                  │
                  o (0..1)
             JobApplication 1 ──< JobApplicationEvent
```

A posting without an application means "not applied". No separate flag exists.

### 4.2 `companies`

| Column | Type | Constraints / notes |
|---|---|---|
| `id` | bigint | PK |
| `name` | varchar(255) | NOT NULL; unique index on `lower(name)` |
| `website` | varchar(2048) | nullable |
| `city` | varchar(255) | nullable |
| `notes` | text | nullable |
| `careers_url` | varchar(2048) | nullable; the company's own careers page |
| `ats` | varchar(50) | nullable; the applicant tracking system it uses (`personio`, `softgarden`, `greenhouse`, …); stored lowercase with whitespace collapsed |
| `ats_jobs_url` | varchar(2048) | nullable; the company's job board on that ATS |
| `created_at`, `updated_at` | timestamptz | |

### 4.3 `job_postings`

| Column | Type | Constraints / notes |
|---|---|---|
| `id` | bigint | PK |
| `company_id` | bigint | FK → `companies.id`, `ON DELETE RESTRICT` |
| `title` | varchar(255) | NOT NULL |
| `url` | varchar(2048) | nullable; partial unique index `WHERE url IS NOT NULL` |
| `source` | varchar(50) | NOT NULL; where the ad was published (`linkedin`, `stepstone`, `xing`, `indeed`, `direct` (the company's own site), `referral`, `arbeitnow`, …); stored lowercase with whitespace collapsed |
| `connector` | varchar(50) | nullable; which Phase 2 connector imported the posting; `NULL` = entered manually |
| `external_id` | varchar(255) | nullable; the posting's ID at the external service |
| `location` | varchar(255) | nullable |
| `work_mode` | varchar | nullable; CHECK in `onsite`, `hybrid`, `remote` |
| `employment_type` | varchar | nullable; CHECK in `full_time`, `part_time`, `contract`, `internship` |
| `seniority` | varchar | nullable; CHECK in `intern`, `junior`, `mid`, `senior`, `lead` |
| `salary_min`, `salary_max` | integer | nullable; CHECK `salary_min <= salary_max` when both are set |
| `salary_currency` | char(3) | NOT NULL, default `EUR` |
| `salary_period` | varchar | nullable; CHECK in `yearly`, `monthly`, `hourly` |
| `description` | text | nullable; plain text, line breaks preserved |
| `raw_payload` | jsonb | nullable; original API response (Phase 2) |
| `posted_at` | date | nullable; the day the ad was published (no time of day) |
| `search_vector` | tsvector | generated, stored: weighted `title` (A) + `description` (B); GIN index |
| `created_at`, `updated_at` | timestamptz | |

Additional constraints:

- unique (`connector`, `external_id`) for Phase 2 deduplication
- index on `company_id`

`source` suggestions in the UI come from a fixed list merged with distinct values already in the table. New values are allowed.

Full-text search uses the `simple` text search configuration. Postings mix English and German, so stemming for one language would mis-handle the other. The configuration name lives in one place so it can be changed later. Slashes are replaced with spaces before indexing, because the parser reads `Laravel/Vue` as a single file-path token. Search terms get the matching treatment: a term with slashes is searched as a phrase, so `Laravel/Vue` finds postings where the two words stand next to each other.

### 4.4 `skills` and `job_posting_skill`

`skills`:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `name` | varchar(100) | NOT NULL; unique index on `lower(name)`; display casing preserved (e.g. `PostgreSQL`) |
| `created_at`, `updated_at` | timestamptz | |

`job_posting_skill`:

- `job_posting_id`: FK, `ON DELETE CASCADE`
- `skill_id`: FK, `ON DELETE CASCADE`
- primary key (`job_posting_id`, `skill_id`)
- index on `skill_id`

Skills are created on the fly from the posting form. Matching an existing skill is case-insensitive.

### 4.5 `job_applications`

| Column | Type | Constraints / notes |
|---|---|---|
| `id` | bigint | PK |
| `job_posting_id` | bigint | FK, **unique**, `ON DELETE RESTRICT` |
| `status` | varchar | NOT NULL; CHECK in `ApplicationStatus` values; denormalized copy of the latest event's `to_status` |
| `applied_at` | timestamptz | nullable; set to the `occurred_at` of the earliest event with `to_status = applied` |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamptz | |

Index on `status`.

**`ApplicationStatus`:**

| Value | Meaning |
|---|---|
| `saved` | Shortlisted; plan to apply |
| `applied` | Application sent |
| `interviewing` | Any interview stage |
| `offer` | Offer received |
| `accepted` | Offer accepted (closed) |
| `rejected` | Rejected by company (closed) |
| `withdrawn` | Candidate withdrew (closed) |

"Closed" = `accepted`, `rejected`, `withdrawn`.

### 4.6 `job_application_events`

| Column | Type | Constraints / notes |
|---|---|---|
| `id` | bigint | PK |
| `job_application_id` | bigint | FK, `ON DELETE CASCADE` |
| `from_status` | varchar | nullable (`NULL` on the creation event); CHECK in statuses |
| `to_status` | varchar | NOT NULL; CHECK in statuses |
| `occurred_at` | timestamptz | NOT NULL; chosen by the user, default now; can be backdated, but not set in the future (5 minutes of tolerance for a browser clock that runs ahead) |
| `note` | text | nullable |
| `created_at` | timestamptz | when the row was actually recorded |

Index on (`job_application_id`, `occurred_at`).

The latest event is the one with the greatest `occurred_at`, with ties broken by greatest `id`.

## 5. Business Rules

All rules live in Action classes. Every multi-row write runs in a single DB transaction.

1. **Create application.** Creating an application also creates its first event (`from_status = NULL`, `to_status = initial status`). The initial status is `saved` or `applied`.
2. **Change status** (`ChangeApplicationStatus`):
   - Any status can move to any other status.
   - Changing to the current status is rejected as a validation error.
   - The new event cannot be dated before the latest event, so it always becomes the latest.
   - The action inserts the event and updates `job_applications.status`.
   - If the application has no `applied_at` and the new status is `applied`, `applied_at` is set to the event's `occurred_at`.
3. **Edit event** (`UpdateApplicationEvent`):
   - Only `occurred_at` and `note` are editable.
   - `from_status` and `to_status` are immutable.
   - The new `occurred_at` keeps the event's place in the timeline: not before the event it follows, not after the event that follows it. Equal times are allowed; ties keep their order by `id`. Otherwise the history could read "applied from saved" before "saved".
   - Because the order never changes, the status stays the same. `applied_at` is recomputed when the earliest `applied` event moves.
4. **Delete event** (`DeleteLatestApplicationEvent`):
   - Only the latest event can be deleted.
   - `status` reverts to that event's `from_status`, and `applied_at` is recomputed.
   - The creation event (`from_status = NULL`) cannot be deleted. Delete the application instead.
5. **Delete application.** Deletes the application and its events. The posting becomes "not applied" again.
6. **Delete posting** (`DeleteJobPosting`). Allowed only when the posting has no application; otherwise a clear error message is shown, so an application's history is never lost by accident. Delete the application first. Deleting a posting removes its skill links.
7. **Delete company.** Allowed only when the company has no postings; otherwise a clear error message is shown.
8. **Consistency invariant.** `job_applications.status` always equals `to_status` of the latest event. Every Action maintains this, and the tests assert it.

### Error handling

- **FormRequests** validate every input. This covers required fields, enum values, `salary_min <= salary_max`, URL format and uniqueness of URL and company name. Uniqueness is checked case-insensitively, so the user sees a friendly message instead of a DB error.
- **Domain rule violations** from Actions (same-status change, an event dated in the future, before the latest event or out of its place in the timeline, deleting a non-latest event, deleting a posting that has an application, deleting a company that has postings) are raised as `ValidationException`. Inertia then shows them inline or as a toast.
- **DB constraints** are the last line of defense, not the primary validation.
- Missing records return the standard 404.

## 6. Screens and User Flows

Every screen is an Inertia page in `resources/js/pages/`. Filters, sorting and pagination live in the query string. Sidebar: the JobTrail logo (only its mark when the sidebar is collapsed to icons), then navigation: **Job Postings · Applications · Companies**. `/` redirects to `/postings`.

### 6.1 Job Postings: index (`/postings`)

- **Columns:** posting (title, with the company below it), location (with the work mode below it), seniority, skills (the first three as chips plus a "+N" chip whose tooltip lists the rest; a skill the list is filtered by is always shown), source, application status badge (or "Not applied").
- **Filters:**
  - full-text search via `search_vector`
  - application state: all / not applied / applied (any application) / a specific status
  - work mode, seniority, skill, source
- **Sort:** created date (default, newest first) or posted date.
- Server-side pagination. A "New posting" button.

### 6.2 Create / edit posting (`/postings/create`, `/postings/{id}/edit`)

- **Company:** combobox. Select an existing company, or create one inline ("Create ‘X’").
- Title, URL, source (suggestions + free entry), location, work mode, employment type, seniority, salary (min, max, currency, period), posted date.
- **Skills:** tag input with autocomplete; Enter creates a new skill.
- **Description:** large plain-text area intended for pasting the full ad.
- **Create only:** an "I already applied" checkbox with a date. When checked, the application is created with status `applied` in the same transaction.

### 6.3 Posting detail (`/postings/{id}`)

- **Main column:** all posting fields and the description, with line breaks preserved.
- **Side panel, no application:** "Save for later" and "Mark as applied" (date/time picker, default now).
- **Side panel, with application:**
  - current status badge and a "Change status" dialog (new status, date/time with default now, optional note)
  - application notes (editable)
  - timeline of events, newest first
  - on each event: edit `occurred_at` and note; delete only on the latest non-creation event
  - "Delete application"
- Edit and delete posting actions.

### 6.4 Applications (`/applications`)

- **Status tabs with counts:** Saved · Applied · Interviewing · Offer · Closed.
- **Columns:** company, posting title, status, applied date, last activity (latest `occurred_at`), days since last activity.
- **Sort:** last activity (default, oldest first within active tabs, to surface follow-ups).
- Rows link to the posting detail page.

### 6.5 Companies (`/companies`, `/companies/{id}`, create/edit)

- **Index:** name, city, website, ATS, posting count, application count.
- **Detail:** company fields (including the careers page, the ATS and its job board, as links), notes, and its postings with application status.
- **Form:** name, website, city, notes, and a "Careers" section: careers page, ATS (suggestions + free entry), job board on the ATS.
- **Delete** is blocked when the company has postings (§5.7).

### 6.6 Shared UI

- Toast notifications after mutations.
- Confirmation dialogs before deletes.
- Empty states with a call to action.
- Inline validation errors.
- Light, dark or system theme, chosen with a toggle in the sidebar footer and kept in a cookie, so the server renders the chosen theme without a flash. System (the default) follows the operating system.

## 7. Testing

### 7.1 Test database

- PostgreSQL only. The schema uses CHECK constraints, expression and partial indexes, `tsvector` and `jsonb`, and SQLite cannot faithfully test those.
- `docker/postgres/init/01-create-testing-db.sql` creates `jobtrail_testing` when the data volume is first initialized.
- `phpunit.xml` points `DB_DATABASE` at `jobtrail_testing`. Tests use `RefreshDatabase`, and the development database is never touched.
- Run the tests with `make test`.

### 7.2 Test suites (Pest)

1. **Actions** (with DB):
   - status change writes the event and updates status atomically
   - `applied_at` is set only by the first `applied`
   - same-status change is rejected
   - editing `occurred_at` recomputes status and `applied_at`
   - deleting the latest event reverts status
   - deleting a non-latest event or the creation event is rejected
   - the consistency invariant (§5.8) holds after every operation
2. **HTTP feature tests:**
   - create a posting with an inline new company, new and existing skills (case-insensitive match) and "already applied"
   - validation failures
   - every index filter, including full-text search
   - posting delete blocked while it has an application
   - company delete blocked
   - `assertInertia` checks that each page gets the correct component and props
3. **DB constraint tests:**
   - case-insensitive unique company and skill names
   - partial unique `url`
   - unique (`connector`, `external_id`)
   - salary CHECK
   - status CHECK
4. **Seeding:**
   - `DemoSeeder` runs cleanly on an empty DB
   - `app:seed-demo-if-empty` does nothing when data exists

### 7.3 Frontend and static checks

No frontend unit tests in Phase 1. `make lint` runs:

- Pint (`--test`)
- Larastan (`composer types:check`)
- `vue-tsc --noEmit` (`npm run types:check`)
- Vite+ lint and format check (`npm run check`)

`npm run build` must also succeed.

## 8. Demo Data

- A factory for every model; tests use them too.
- `DemoSeeder` uses a fixed Faker seed, so the output is identical on every `make fresh`.
- The data is realistic for the German market and entirely fictional:
  - ~12 fictional companies (e.g. "Nordlicht Software GmbH") in Berlin, München, Hamburg, Köln, or remote
  - about half of them with a careers page, an ATS and its job board URL (all on `.example` hosts)
  - ~40 postings with varied work modes, seniorities, salaries and sources
  - ~30 skills (PHP, Laravel, Symfony, Vue, React, TypeScript, PostgreSQL, MySQL, Docker, Kubernetes, AWS, Go, …), attached with a weighted distribution so the "most requested skills" analysis looks meaningful
  - ~25 applications across all statuses, with plausible, chronologically consistent event histories over the last ~3 months
  - most histories belong to the oldest postings; a few of the newest postings get a short, fresh one, so the default postings list (newest first) shows status badges too
- The seeded data must satisfy the consistency invariant (§5.8). It is built through the same Actions or verified by the seeder test.

## 9. CI (GitHub Actions)

Workflow file: `.github/workflows/ci.yml`. It runs on every push and pull request.

- **Service container:** `postgres:18` with a healthcheck; creates `jobtrail_testing`.
- **Steps:**
  1. checkout
  2. set up PHP 8.4 (with `pdo_pgsql`, `intl`) and Node 24
  3. cache Composer and npm dependencies
  4. `composer install`, `npm ci`
  5. `npm run build`
  6. Pint `--test`, Larastan, `vue-tsc`, `npm run check`
  7. `php artisan test`
- README shows the CI status badge.

## 10. README

- What JobTrail is, with screenshots from the demo data.
- Quick start: `git clone` → `docker compose up` → open `http://localhost:8080`.
- Service URLs (app, pgAdmin with credentials) and Makefile commands.
- Data persistence rules (§3.3).
- Tech stack and a short architecture overview.
- Roadmap (§1).

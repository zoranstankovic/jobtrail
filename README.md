# JobTrail

[![CI](https://github.com/zoranstankovic/jobtrail/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/zoranstankovic/jobtrail/actions/workflows/ci.yml)

JobTrail is a personal job search tracker that runs on your machine. It keeps the job postings you find and the companies behind them, shows at a glance which postings you applied to, and records every status change of an application as a timeline.

It is a single-user app without a login, built with Laravel 13, Vue 3 and PostgreSQL 18. One command starts everything, demo data included.

![Job postings with search, filters, skills and application status](docs/screenshots/postings.png)

## Features

- **Job postings** with company, location, work mode, seniority, salary, source, skills and the full text of the ad.
- **Search and filters:** full-text search over title and description; filters for application state, work mode, seniority, skill and source.
- **Companies** as their own records, created inline while adding a posting.
- **Applications** with seven statuses (Saved, Applied, Interviewing, Offer, Accepted, Rejected, Withdrawn) and a dated history. Entries can be backdated, edited and undone, and the history always stays in order.
- **Follow-ups first:** the Applications view groups applications by status and lists the ones with the oldest activity first.
- **Demo data:** 12 fictional companies, 40 postings and 25 applications from the German job market, recreated identically on every reset.

![A posting with its application history](docs/screenshots/posting-detail.png)

![Applications grouped by status](docs/screenshots/applications.png)

## Quick start

You need Docker with Compose v2 (for example Docker Desktop) and free ports 8080, 5173, 5432 and 5050. JobTrail is developed and tested on macOS with Docker Desktop.

```bash
git clone https://github.com/zoranstankovic/jobtrail.git
cd jobtrail
docker compose up
```

The first start builds the image and installs the Composer and npm dependencies, which takes a few minutes. When the log shows `[entrypoint] ready` and the Vite dev server has started, open <http://localhost:8080>.

There is nothing else to set up: `.env` is created from `.env.example` with a new application key, the migrations run, and the demo data is seeded into the empty database.

## Services

| Address                 | Service                                                             |
| ----------------------- | ------------------------------------------------------------------- |
| <http://localhost:8080> | JobTrail (nginx in front of PHP-FPM)                                |
| <http://localhost:5050> | pgAdmin, signed in as `admin@jobtrail.local` / `secret`             |
| `localhost:5432`        | PostgreSQL: database `jobtrail`, user `jobtrail`, password `secret` |
| <http://localhost:5173> | Vite dev server (serves the frontend with hot reload)               |

pgAdmin already lists the server "JobTrail (db)"; the first time you open it, it asks for the database password (`secret`) and offers to save it. These credentials are for local development only.

## Everyday commands

Run `make` to list them. They run inside the containers, so PHP, Composer and Node are not needed on the host.

| Command                         | What it does                                                             |
| ------------------------------- | ------------------------------------------------------------------------ |
| `make up`                       | Start the stack in the background                                        |
| `make down`                     | Stop the stack; data is kept                                             |
| `make logs`                     | Follow the logs of every service                                         |
| `make shell`                    | Open a shell in the `app` container                                      |
| `make test`                     | Code style (Pint), static analysis (Larastan) and the tests              |
| `make lint`                     | Every static check, PHP and frontend, without changing files             |
| `make fresh`                    | Reset the database to the demo data (deletes your data)                  |
| `make artisan cmd="route:list"` | Run an Artisan command; `make composer` and `make npm` work the same way |

## Your data

The database lives in a Docker volume, so it survives restarts. Nothing destructive ever runs automatically.

| Action                                               | Result                                                       |
| ---------------------------------------------------- | ------------------------------------------------------------ |
| `docker compose stop`, Ctrl+C, `docker compose down` | Data is kept                                                 |
| `docker compose up` again                            | Same data; only new migrations run, nothing is reseeded      |
| `make fresh`                                         | Explicit reset to the demo data                              |
| `docker compose down -v`                             | Deletes the volume; the next start seeds the demo data again |

## Troubleshooting

- **A port is already in use:** stop the program that uses it (often a local PostgreSQL on 5432), or change the host side of that port in `compose.yaml`, e.g. `"5433:5432"`.
- **Start from a clean state:** `docker compose down -v`, then `docker compose up`.

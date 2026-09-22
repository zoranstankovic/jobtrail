<?php

namespace Database\Seeders;

use App\Actions\ChangeApplicationStatus;
use App\Actions\CreateJobApplication;
use App\Actions\CreateJobPosting;
use App\Actions\ResolveSkills;
use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Database\Seeder;
use LogicException;

/**
 * Fictional demo data for the German market (docs/design.md §8).
 *
 * Everything goes through the Actions, so the seeded applications follow the
 * same rules as data entered by hand. The fixed Faker seed makes every run
 * produce the same records; dates are offsets from "now", so the demo always
 * looks recent. Every random draw must go through $faker (mt_rand) to stay
 * deterministic.
 */
class DemoSeeder extends Seeder
{
    private const FAKER_SEED = 20260915;

    private const POSTING_COUNT = 40;

    private const APPLICATION_COUNT = 25;

    /**
     * @var list<array{name: string, city: string|null, website: string, notes: string|null}>
     */
    private const COMPANIES = [
        ['name' => 'Nordlicht Software GmbH', 'city' => 'Hamburg', 'website' => 'https://nordlicht-software.example', 'notes' => null],
        ['name' => 'Isarwerk Digital GmbH', 'city' => 'München', 'website' => 'https://isarwerk-digital.example', 'notes' => null],
        ['name' => 'Spreeblick Labs GmbH', 'city' => 'Berlin', 'website' => 'https://spreeblick-labs.example', 'notes' => 'Friendly team, fast hiring process.'],
        ['name' => 'Domblick Systems GmbH', 'city' => 'Köln', 'website' => 'https://domblick-systems.example', 'notes' => null],
        ['name' => 'Elbfeld Technologies GmbH', 'city' => 'Hamburg', 'website' => 'https://elbfeld-tech.example', 'notes' => null],
        ['name' => 'Bergquelle Data AG', 'city' => 'München', 'website' => 'https://bergquelle-data.example', 'notes' => 'Large data platform team.'],
        ['name' => 'Kranich Commerce GmbH', 'city' => 'Berlin', 'website' => 'https://kranich-commerce.example', 'notes' => null],
        ['name' => 'Rheinbogen IT Solutions GmbH', 'city' => 'Köln', 'website' => 'https://rheinbogen-it.example', 'notes' => null],
        ['name' => 'Lindenhof Health Tech GmbH', 'city' => 'Berlin', 'website' => 'https://lindenhof-health.example', 'notes' => null],
        ['name' => 'Seewind Mobility GmbH', 'city' => 'München', 'website' => 'https://seewind-mobility.example', 'notes' => null],
        ['name' => 'Fernweh Travel Tech GmbH', 'city' => null, 'website' => 'https://fernweh-travel.example', 'notes' => 'Fully remote company.'],
        ['name' => 'Wolkenbruch Cloud GmbH', 'city' => 'Berlin', 'website' => 'https://wolkenbruch-cloud.example', 'notes' => null],
    ];

    /**
     * Skill name => relative weight. Higher weights appear in more postings,
     * so a "most requested skills" chart has a clear shape.
     *
     * @var array<string, int>
     */
    private const SKILLS = [
        'PHP' => 10,
        'Laravel' => 9,
        'Docker' => 8,
        'TypeScript' => 8,
        'JavaScript' => 7,
        'PostgreSQL' => 7,
        'Vue' => 7,
        'Git' => 6,
        'React' => 6,
        'REST APIs' => 6,
        'AWS' => 5,
        'CI/CD' => 5,
        'MySQL' => 5,
        'Kubernetes' => 4,
        'Linux' => 4,
        'Node.js' => 4,
        'Python' => 4,
        'Symfony' => 4,
        'Go' => 3,
        'GraphQL' => 3,
        'Microservices' => 3,
        'Redis' => 3,
        'Tailwind CSS' => 3,
        'Azure' => 2,
        'Elasticsearch' => 2,
        'Java' => 2,
        'Nuxt' => 2,
        'RabbitMQ' => 2,
        'Terraform' => 2,
        'Kotlin' => 1,
    ];

    /**
     * @var list<string>
     */
    private const ROLES = [
        'PHP Developer',
        'Backend Engineer',
        'Full Stack Developer (Laravel/Vue)',
        'Software Engineer',
        'Frontend Developer (Vue.js)',
        'DevOps Engineer',
        'Platform Engineer',
        'Web Developer',
        'Softwareentwickler PHP (m/w/d)',
        'Fullstack-Entwickler (m/w/d)',
        'Cloud Engineer',
        'Backend Developer (Go)',
    ];

    /**
     * @var list<string>
     */
    private const SOURCES = ['linkedin', 'stepstone', 'xing', 'indeed', 'direct', 'referral', 'arbeitnow'];

    /**
     * Status histories. The first ten are used once each, so every status
     * appears; the rest of the applications pick one at random.
     *
     * @var list<list<ApplicationStatus>>
     */
    private const HISTORIES = [
        [ApplicationStatus::Saved],
        [ApplicationStatus::Applied],
        [ApplicationStatus::Saved, ApplicationStatus::Applied],
        [ApplicationStatus::Applied, ApplicationStatus::Interviewing],
        [ApplicationStatus::Applied, ApplicationStatus::Interviewing, ApplicationStatus::Offer],
        [ApplicationStatus::Applied, ApplicationStatus::Interviewing, ApplicationStatus::Offer, ApplicationStatus::Accepted],
        [ApplicationStatus::Applied, ApplicationStatus::Rejected],
        [ApplicationStatus::Applied, ApplicationStatus::Interviewing, ApplicationStatus::Rejected],
        [ApplicationStatus::Saved, ApplicationStatus::Applied, ApplicationStatus::Withdrawn],
        [ApplicationStatus::Applied, ApplicationStatus::Interviewing, ApplicationStatus::Withdrawn],
    ];

    /**
     * Short histories for a few of the newest postings, keyed by position in
     * the newest-first order, so the default postings list shows status
     * badges and not only "Not applied".
     *
     * @var array<int, list<ApplicationStatus>>
     */
    private const RECENT_HISTORIES = [
        0 => [ApplicationStatus::Saved],
        2 => [ApplicationStatus::Applied],
        4 => [ApplicationStatus::Saved, ApplicationStatus::Applied],
        7 => [ApplicationStatus::Applied, ApplicationStatus::Interviewing],
    ];

    /**
     * @var list<string>
     */
    private const EVENT_NOTES = [
        'Phone screen with the recruiter.',
        'Technical interview with two engineers.',
        'Take-home assignment sent.',
        'Follow-up email sent.',
        'Salary expectations discussed.',
        'Met the team on site.',
    ];

    public function run(
        CreateJobPosting $createJobPosting,
        CreateJobApplication $createJobApplication,
        ChangeApplicationStatus $changeApplicationStatus,
        ResolveSkills $resolveSkills,
    ): void {
        $faker = fake();

        // Faker\Generator::__destruct() reseeds mt_rand at random. Collect any
        // unreachable generators (for example from earlier test applications)
        // now, so a garbage-collection run cannot reseed in the middle of the
        // demo and break determinism.
        gc_collect_cycles();
        $faker->seed(self::FAKER_SEED);

        $now = CarbonImmutable::now()->startOfMinute();

        $companies = array_map(
            fn (array $attributes): Company => Company::query()->create($attributes),
            self::COMPANIES,
        );

        // Create every skill up front, so all 30 exist even if the weighted
        // draw never picks a rare one.
        $resolveSkills->handle(array_keys(self::SKILLS));

        $postings = [];

        for ($number = 1; $number <= self::POSTING_COUNT; $number++) {
            $postings[] = $this->createPosting($createJobPosting, $faker, $faker->randomElement($companies), $now, $number);
        }

        // The oldest postings have had time for a long history. usort is stable.
        usort($postings, fn (array $a, array $b): int => $a['created_at'] <=> $b['created_at']);

        $longHistoryCount = self::APPLICATION_COUNT - count(self::RECENT_HISTORIES);

        foreach (array_slice($postings, 0, $longHistoryCount) as $index => $entry) {
            $history = self::HISTORIES[$index] ?? $faker->randomElement(self::HISTORIES);

            $this->createHistory(
                $createJobApplication,
                $changeApplicationStatus,
                $faker,
                $entry['posting'],
                $entry['created_at'],
                $history,
                $now,
            );
        }

        // Created after the long histories, so those consume the same Faker
        // draws as before and stay unchanged.
        $newestFirst = array_reverse($postings);

        foreach (self::RECENT_HISTORIES as $position => $history) {
            $this->createHistory(
                $createJobApplication,
                $changeApplicationStatus,
                $faker,
                $newestFirst[$position]['posting'],
                $newestFirst[$position]['created_at'],
                $history,
                $now,
            );
        }
    }

    /**
     * @return array{posting: JobPosting, created_at: CarbonImmutable}
     */
    private function createPosting(
        CreateJobPosting $createJobPosting,
        Generator $faker,
        Company $company,
        CarbonImmutable $now,
        int $number,
    ): array {
        $seniority = $faker->randomElement([
            Seniority::Intern,
            Seniority::Junior,
            Seniority::Mid,
            Seniority::Mid,
            Seniority::Senior,
            Seniority::Senior,
            Seniority::Senior,
            Seniority::Lead,
        ]);
        $workMode = $faker->randomElement(WorkMode::cases());
        $skills = $this->pickSkills($faker, $faker->numberBetween(3, 6));
        $title = $this->title($seniority, $faker->randomElement(self::ROLES));
        $location = $workMode === WorkMode::Remote ? 'Remote (Germany)' : ($company->city ?? 'Berlin');
        $createdAt = $now->subDays($faker->numberBetween(3, 90))->subMinutes($faker->numberBetween(0, 1439));

        $posting = $createJobPosting->handle($company, [
            'title' => $title,
            'url' => "https://jobs.example/postings/{$number}",
            'source' => $faker->randomElement(self::SOURCES),
            'location' => $location,
            'work_mode' => $workMode,
            'employment_type' => $seniority === Seniority::Intern
                ? EmploymentType::Internship
                : $faker->randomElement([
                    EmploymentType::FullTime,
                    EmploymentType::FullTime,
                    EmploymentType::FullTime,
                    EmploymentType::FullTime,
                    EmploymentType::PartTime,
                    EmploymentType::Contract,
                ]),
            'seniority' => $seniority,
            ...$this->salary($faker, $seniority),
            'description' => $this->description($company, $title, $location, $workMode, $skills),
            'posted_at' => $faker->boolean(80) ? $createdAt->subDays($faker->numberBetween(0, 5)) : null,
        ], $skills);

        $posting->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return ['posting' => $posting, 'created_at' => $createdAt];
    }

    /**
     * Replays a status history through the Actions, spreading the events
     * between the posting's creation and now.
     *
     * @param  list<ApplicationStatus>  $history
     */
    private function createHistory(
        CreateJobApplication $createJobApplication,
        ChangeApplicationStatus $changeApplicationStatus,
        Generator $faker,
        JobPosting $posting,
        CarbonImmutable $createdAt,
        array $history,
        CarbonImmutable $now,
    ): void {
        // n steps of at most one slice each stay below the n + 1 slices of
        // available time, so no event lands in the future.
        $maxGapHours = max(1, intdiv((int) $createdAt->diffInHours($now), count($history) + 1));
        $occurredAt = $createdAt;
        $application = null;

        foreach ($history as $status) {
            $occurredAt = $occurredAt->addHours($faker->numberBetween(1, $maxGapHours));
            $note = $faker->optional(0.4)->randomElement(self::EVENT_NOTES);

            if ($application === null) {
                $application = $createJobApplication->handle($posting, $status, $occurredAt);
            } else {
                $changeApplicationStatus->handle($application, $status, $occurredAt, $note);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function pickSkills(Generator $faker, int $count): array
    {
        $picked = [];

        while (count($picked) < $count) {
            $picked[$this->weightedSkill($faker)] = true;
        }

        return array_keys($picked);
    }

    private function weightedSkill(Generator $faker): string
    {
        $roll = $faker->numberBetween(1, array_sum(self::SKILLS));

        foreach (self::SKILLS as $name => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $name;
            }
        }

        throw new LogicException('The weighted skill draw fell through.');
    }

    /**
     * @return array{salary_min: int|null, salary_max: int|null, salary_period: SalaryPeriod|null}
     */
    private function salary(Generator $faker, Seniority $seniority): array
    {
        if ($faker->boolean(30)) {
            return ['salary_min' => null, 'salary_max' => null, 'salary_period' => null];
        }

        [$low, $high, $period] = match ($seniority) {
            Seniority::Intern => [1200, 2000, SalaryPeriod::Monthly],
            Seniority::Junior => [42000, 55000, SalaryPeriod::Yearly],
            Seniority::Mid => [55000, 70000, SalaryPeriod::Yearly],
            Seniority::Senior => [70000, 90000, SalaryPeriod::Yearly],
            Seniority::Lead => [85000, 115000, SalaryPeriod::Yearly],
        };

        $step = $period === SalaryPeriod::Monthly ? 100 : 1000;
        $min = $faker->numberBetween(intdiv($low, $step), intdiv($high, $step) - 5) * $step;

        return [
            'salary_min' => $min,
            'salary_max' => $min + $faker->numberBetween(2, 5) * $step,
            'salary_period' => $period,
        ];
    }

    private function title(Seniority $seniority, string $role): string
    {
        return match ($seniority) {
            Seniority::Intern => "Working Student – {$role}",
            Seniority::Junior => "Junior {$role}",
            Seniority::Mid => $role,
            Seniority::Senior => "Senior {$role}",
            Seniority::Lead => "Lead {$role}",
        };
    }

    /**
     * @param  list<string>  $skills
     */
    private function description(Company $company, string $title, string $location, WorkMode $workMode, array $skills): string
    {
        $workModeText = match ($workMode) {
            WorkMode::Onsite => 'a modern office',
            WorkMode::Hybrid => 'a hybrid setup with two office days a week',
            WorkMode::Remote => 'fully remote work within Germany',
        };

        return implode("\n", [
            "{$company->name} is looking for a {$title} in {$location}.",
            '',
            'Your tasks:',
            "- Build and maintain features with {$skills[0]}",
            '- Work closely with product, design and QA',
            '- Take part in code reviews and share knowledge',
            '',
            'Your profile:',
            '- Hands-on experience with '.implode(', ', $skills),
            '- Good English; German is a plus',
            '',
            "We offer {$workModeText}, 30 days of vacation and a yearly learning budget.",
        ]);
    }
}

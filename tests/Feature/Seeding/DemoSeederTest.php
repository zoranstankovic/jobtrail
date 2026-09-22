<?php

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobPosting;
use App\Models\Skill;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;

it('seeds the demo data into an empty database', function (): void {
    $this->seed(DemoSeeder::class);

    expect(Company::query()->count())->toBe(12)
        ->and(Skill::query()->count())->toBe(30)
        ->and(JobPosting::query()->count())->toBe(40)
        ->and(JobApplication::query()->count())->toBe(25);
});

it('covers every application status', function (): void {
    $this->seed(DemoSeeder::class);

    expect(DB::table('job_applications')->distinct()->orderBy('status')->pluck('status')->all())
        ->toBe(['accepted', 'applied', 'interviewing', 'offer', 'rejected', 'saved', 'withdrawn']);
});

it('produces consistent applications with no events in the future', function (): void {
    $this->seed(DemoSeeder::class);

    JobApplication::query()->each(function (JobApplication $application): void {
        expectApplicationToBeConsistent($application);
    });

    expect(JobApplicationEvent::query()->where('occurred_at', '>', CarbonImmutable::now())->exists())->toBeFalse();
});

it('gives every posting at least one skill', function (): void {
    $this->seed(DemoSeeder::class);

    expect(JobPosting::query()->doesntHave('skills')->exists())->toBeFalse();
});

it('produces the same records on every run', function (): void {
    $fingerprint = fn (): array => JobPosting::query()
        ->with(['company', 'skills', 'application'])
        ->orderBy('url')
        ->get()
        ->map(fn (JobPosting $posting): array => [
            $posting->url,
            $posting->title,
            $posting->company->name,
            $posting->salary_min,
            $posting->skills->pluck('name')->sort()->values()->all(),
            $posting->application?->status->value,
        ])
        ->all();

    $this->seed(DemoSeeder::class);
    $first = $fingerprint();

    // Applications cascade to events; postings with applications are
    // protected by RESTRICT, so applications go first.
    JobApplication::query()->delete();
    JobPosting::query()->delete();
    Company::query()->delete();
    Skill::query()->delete();

    $this->seed(DemoSeeder::class);

    expect($fingerprint())->toBe($first);
});

it('gives some of the newest postings a fresh application', function (): void {
    $this->seed(DemoSeeder::class);

    // The postings index sorts like this by default (newest first).
    $statuses = JobPosting::query()
        ->with('application')
        ->latest()
        ->orderByDesc('id')
        ->limit(10)
        ->get()
        ->map(fn (JobPosting $posting): ?string => $posting->application?->status->value)
        ->all();

    expect($statuses)->toBe([
        'saved', null, 'applied', null, 'applied', null, null, 'interviewing', null, null,
    ]);
});

it('gives some demo companies a careers page and an ATS', function (): void {
    $this->seed(DemoSeeder::class);

    expect(Company::query()->whereNotNull('careers_url')->count())->toBe(7)
        ->and(Company::query()->whereNotNull('ats')->count())->toBe(6)
        ->and(Company::query()->whereNotNull('ats_jobs_url')->count())->toBe(6)
        ->and(Company::query()->whereNotNull('ats_jobs_url')->pluck('ats_jobs_url')
            ->every(fn (string $url): bool => str_contains(parse_url($url, PHP_URL_HOST) ?: '', '.example')))->toBeTrue();
});

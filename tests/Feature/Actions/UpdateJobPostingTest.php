<?php

use App\Actions\UpdateJobPosting;
use App\Enums\ApplicationStatus;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Skill;
use Illuminate\Database\QueryException;

it('updates the fields, moves the posting to another company and replaces the skills', function (): void {
    $posting = JobPosting::factory()->create(['title' => 'Old title', 'work_mode' => WorkMode::Onsite]);
    $posting->skills()->attach(Skill::factory()->create(['name' => 'PHP'])->id);
    $target = Company::factory()->create(['name' => 'Acme GmbH']);
    Skill::factory()->create(['name' => 'Laravel']);

    app(UpdateJobPosting::class)->handle(
        $posting,
        'ACME GMBH',
        ['title' => 'New title', 'work_mode' => WorkMode::Remote],
        ['laravel', 'Vue'],
    );

    $fresh = $posting->fresh();

    expect($fresh?->title)->toBe('New title')
        ->and($fresh?->work_mode)->toBe(WorkMode::Remote)
        ->and($fresh?->company_id)->toBe($target->id)
        ->and($fresh?->skills()->orderBy('name')->pluck('name')->all())->toBe(['Laravel', 'Vue'])
        ->and(Skill::query()->count())->toBe(3);
});

it('creates a new company inline', function (): void {
    $posting = JobPosting::factory()->create();

    app(UpdateJobPosting::class)->handle($posting, 'Nordlicht Software GmbH', []);

    expect($posting->fresh()?->company->name)->toBe('Nordlicht Software GmbH');
});

it('leaves the application untouched', function (): void {
    $application = createApplication(ApplicationStatus::Applied);
    $posting = $application->jobPosting;

    app(UpdateJobPosting::class)->handle($posting, $posting->company, ['title' => 'Renamed']);

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Applied);

    expectApplicationToBeConsistent($application);
});

it('writes nothing when a step fails', function (): void {
    $posting = JobPosting::factory()->create(['title' => 'Original']);

    expect(fn () => app(UpdateJobPosting::class)->handle(
        $posting,
        'Nordlicht Software GmbH',
        ['title' => 'Changed', 'salary_min' => 90000, 'salary_max' => 50000],
    ))->toThrow(QueryException::class, 'job_postings_salary_check');

    expect($posting->fresh()?->title)->toBe('Original')
        ->and(Company::query()->where('name', 'Nordlicht Software GmbH')->exists())->toBeFalse();
});

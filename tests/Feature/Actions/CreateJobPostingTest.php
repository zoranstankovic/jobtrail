<?php

use App\Actions\CreateJobPosting;
use App\Enums\ApplicationStatus;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Skill;
use Carbon\CarbonImmutable;

it('creates a posting for an existing company', function (): void {
    $company = Company::factory()->create();

    $posting = app(CreateJobPosting::class)->handle($company, [
        'title' => 'Senior Laravel Developer',
        'source' => 'StepStone',
        'work_mode' => WorkMode::Hybrid,
        'seniority' => 'senior',
        'salary_min' => 70000,
        'salary_max' => 85000,
    ]);

    $fresh = $posting->fresh();

    expect($fresh?->company->is($company))->toBeTrue()
        ->and($fresh?->title)->toBe('Senior Laravel Developer')
        ->and($fresh?->source)->toBe('stepstone')
        ->and($fresh?->work_mode)->toBe(WorkMode::Hybrid)
        ->and($fresh?->seniority)->toBe(Seniority::Senior)
        ->and($fresh?->application)->toBeNull();
});

it('creates the company inline from a name', function (): void {
    $posting = app(CreateJobPosting::class)->handle('  Isarwerk Digital GmbH ', [
        'title' => 'Backend Engineer',
        'source' => 'linkedin',
    ]);

    expect($posting->company->name)->toBe('Isarwerk Digital GmbH')
        ->and(Company::query()->count())->toBe(1);
});

it('reuses an existing company when the inline name differs only in case', function (): void {
    $existing = Company::factory()->create(['name' => 'Isarwerk Digital GmbH']);

    $posting = app(CreateJobPosting::class)->handle('ISARWERK digital gmbh', [
        'title' => 'Backend Engineer',
        'source' => 'linkedin',
    ]);

    expect($posting->company->is($existing))->toBeTrue()
        ->and(Company::query()->count())->toBe(1);
});

it('attaches new and existing skills, matching case-insensitively', function (): void {
    Skill::factory()->create(['name' => 'PostgreSQL']);

    $posting = app(CreateJobPosting::class)->handle(Company::factory()->create(), [
        'title' => 'Backend Engineer',
        'source' => 'linkedin',
    ], ['postgresql', 'Laravel']);

    expect($posting->skills()->orderBy('name')->pluck('name')->all())->toBe(['Laravel', 'PostgreSQL'])
        ->and(Skill::query()->count())->toBe(2);
});

it('creates an applied application when the posting was already applied to', function (): void {
    $posting = app(CreateJobPosting::class)->handle(Company::factory()->create(), [
        'title' => 'Backend Engineer',
        'source' => 'linkedin',
    ], [], CarbonImmutable::parse('2026-09-01 10:00:00'));

    $application = $posting->application;

    expect($application?->status)->toBe(ApplicationStatus::Applied)
        ->and($application?->applied_at?->toDateTimeString())->toBe('2026-09-01 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('writes nothing when creating the application fails', function (): void {
    JobApplication::creating(function (): void {
        throw new RuntimeException('Simulated failure while creating the application.');
    });

    expect(fn () => app(CreateJobPosting::class)->handle('Isarwerk Digital GmbH', [
        'title' => 'Backend Engineer',
        'source' => 'linkedin',
    ], ['Laravel'], CarbonImmutable::parse('2026-09-01 10:00:00')))
        ->toThrow(RuntimeException::class, 'Simulated failure');

    expect(JobPosting::query()->count())->toBe(0)
        ->and(Company::query()->count())->toBe(0)
        ->and(Skill::query()->count())->toBe(0);
});

<?php

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Models\JobPosting;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('creates a saved application with its creation event', function (): void {
    $posting = JobPosting::factory()->create();

    $application = app(CreateJobApplication::class)->handle(
        $posting,
        ApplicationStatus::Saved,
        CarbonImmutable::parse('2026-09-01 10:00:00'),
        'Looks interesting',
    );

    $event = $application->events()->sole();

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Saved)
        ->and($application->fresh()?->applied_at)->toBeNull()
        ->and($application->fresh()?->notes)->toBe('Looks interesting')
        ->and($event->from_status)->toBeNull()
        ->and($event->to_status)->toBe(ApplicationStatus::Saved)
        ->and($event->occurred_at->toDateTimeString())->toBe('2026-09-01 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('sets applied_at when the application starts as applied', function (): void {
    $application = app(CreateJobApplication::class)->handle(
        JobPosting::factory()->create(),
        ApplicationStatus::Applied,
        CarbonImmutable::parse('2026-09-01 10:00:00'),
    );

    expect($application->fresh()?->applied_at?->toDateTimeString())->toBe('2026-09-01 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('dates the creation event now when no date is given', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-10 08:30:00'));

    $application = app(CreateJobApplication::class)->handle(JobPosting::factory()->create());

    expect($application->events()->sole()->occurred_at->toDateTimeString())->toBe('2026-09-10 08:30:00');
});

it('rejects an initial status other than saved or applied', function (ApplicationStatus $status): void {
    $posting = JobPosting::factory()->create();

    expect(fn () => app(CreateJobApplication::class)->handle($posting, $status))
        ->toThrow(ValidationException::class, 'must start as saved or applied');

    expect($posting->application()->exists())->toBeFalse();
})->with([
    ApplicationStatus::Interviewing,
    ApplicationStatus::Offer,
    ApplicationStatus::Accepted,
    ApplicationStatus::Rejected,
    ApplicationStatus::Withdrawn,
]);

it('rejects a second application for the same posting', function (): void {
    $posting = JobPosting::factory()->create();
    app(CreateJobApplication::class)->handle($posting);

    expect(fn () => app(CreateJobApplication::class)->handle($posting))
        ->toThrow(ValidationException::class, 'already has an application');
});

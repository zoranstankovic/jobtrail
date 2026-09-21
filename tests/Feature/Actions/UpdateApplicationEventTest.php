<?php

use App\Actions\ChangeApplicationStatus;
use App\Actions\UpdateApplicationEvent;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

function recordStatus(JobApplication $application, ApplicationStatus $status, string $occurredAt): JobApplicationEvent
{
    return app(ChangeApplicationStatus::class)->handle($application, $status, CarbonImmutable::parse($occurredAt));
}

function redate(JobApplicationEvent $event, string $occurredAt, ?string $note = null): void
{
    app(UpdateApplicationEvent::class)->handle($event, CarbonImmutable::parse($occurredAt), $note);
}

it('updates the date and the note but not the statuses', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $event = recordStatus($application, ApplicationStatus::Applied, '2026-09-02 10:00:00');

    redate($event, '2026-09-02 16:30:00', 'Sent via the company website');

    $fresh = $event->fresh();

    expect($fresh?->occurred_at->toDateTimeString())->toBe('2026-09-02 16:30:00')
        ->and($fresh?->note)->toBe('Sent via the company website')
        ->and($fresh?->from_status)->toBe(ApplicationStatus::Saved)
        ->and($fresh?->to_status)->toBe(ApplicationStatus::Applied);

    expectApplicationToBeConsistent($application);
});

it('rejects a date before the event it follows', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    recordStatus($application, ApplicationStatus::Applied, '2026-09-03 10:00:00');
    $interviewing = recordStatus($application, ApplicationStatus::Interviewing, '2026-09-05 10:00:00');

    expect(fn () => redate($interviewing, '2026-09-02 10:00:00'))
        ->toThrow(ValidationException::class, 'cannot be dated before the event it follows');

    expect($interviewing->fresh()?->occurred_at->toDateTimeString())->toBe('2026-09-05 10:00:00')
        ->and($application->fresh()?->status)->toBe(ApplicationStatus::Interviewing);
});

it('rejects a date after the event that follows it', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    recordStatus($application, ApplicationStatus::Rejected, '2026-09-05 10:00:00');
    $creation = $application->events()->whereNull('from_status')->sole();

    expect(fn () => redate($creation, '2026-09-10 10:00:00'))
        ->toThrow(ValidationException::class, 'cannot be dated after the event that follows it');

    expect($application->fresh()?->applied_at?->toDateTimeString())->toBe('2026-09-01 10:00:00');
});

it('accepts the same time as a neighbour and keeps the order', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $applied = recordStatus($application, ApplicationStatus::Applied, '2026-09-03 10:00:00');
    recordStatus($application, ApplicationStatus::Interviewing, '2026-09-05 10:00:00');

    redate($applied, '2026-09-05 10:00:00');

    $fresh = $application->fresh();

    expect($fresh?->status)->toBe(ApplicationStatus::Interviewing)
        ->and($fresh?->applied_at?->toDateTimeString())->toBe('2026-09-05 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('moves the first event freely into the past and the latest one forward', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    $interviewing = recordStatus($application, ApplicationStatus::Interviewing, '2026-09-05 10:00:00');
    $creation = $application->events()->whereNull('from_status')->sole();

    redate($creation, '2026-06-01 10:00:00');
    redate($interviewing, '2026-09-08 10:00:00');

    $fresh = $application->fresh();

    expect($fresh?->status)->toBe(ApplicationStatus::Interviewing)
        ->and($fresh?->applied_at?->toDateTimeString())->toBe('2026-06-01 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('leaves status and applied_at alone when the order does not change', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    $interviewing = recordStatus($application, ApplicationStatus::Interviewing, '2026-09-05 10:00:00');

    redate($interviewing, '2026-09-06 10:00:00', 'Moved by a day');

    $fresh = $application->fresh();

    expect($fresh?->status)->toBe(ApplicationStatus::Interviewing)
        ->and($fresh?->applied_at?->toDateTimeString())->toBe('2026-09-01 10:00:00');

    expectApplicationToBeConsistent($application);
});

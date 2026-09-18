<?php

use App\Actions\ChangeApplicationStatus;
use App\Actions\UpdateApplicationEvent;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Carbon\CarbonImmutable;

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

it('recomputes the status when the latest event moves before another one', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    recordStatus($application, ApplicationStatus::Applied, '2026-09-03 10:00:00');
    $interviewing = recordStatus($application, ApplicationStatus::Interviewing, '2026-09-05 10:00:00');

    // Moved between "saved" and "applied": "applied" becomes the latest event.
    redate($interviewing, '2026-09-02 10:00:00');

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Applied);

    expectApplicationToBeConsistent($application);
});

it('recomputes applied_at when the earliest applied event moves', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    recordStatus($application, ApplicationStatus::Rejected, '2026-09-05 10:00:00');
    recordStatus($application, ApplicationStatus::Applied, '2026-09-08 10:00:00');
    $creation = $application->events()->whereNull('from_status')->sole();

    // The creation event moves after everything else.
    redate($creation, '2026-09-10 10:00:00');

    $fresh = $application->fresh();

    expect($fresh?->status)->toBe(ApplicationStatus::Applied)
        ->and($fresh?->applied_at?->toDateTimeString())->toBe('2026-09-08 10:00:00');

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

<?php

use App\Actions\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

function changeStatus(JobApplication $application, ApplicationStatus $status, string $occurredAt, ?string $note = null): void
{
    app(ChangeApplicationStatus::class)->handle($application, $status, CarbonImmutable::parse($occurredAt), $note);
}

it('records the change as an event and updates the status', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');

    $event = app(ChangeApplicationStatus::class)->handle(
        $application,
        ApplicationStatus::Interviewing,
        CarbonImmutable::parse('2026-09-05 14:00:00'),
        'First call with HR',
    );

    expect($event->from_status)->toBe(ApplicationStatus::Applied)
        ->and($event->to_status)->toBe(ApplicationStatus::Interviewing)
        ->and($event->note)->toBe('First call with HR')
        ->and($application->fresh()?->status)->toBe(ApplicationStatus::Interviewing);

    expectApplicationToBeConsistent($application);
});

it('sets applied_at on the first change to applied', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');

    changeStatus($application, ApplicationStatus::Applied, '2026-09-03 09:00:00');

    expect($application->fresh()?->applied_at?->toDateTimeString())->toBe('2026-09-03 09:00:00');

    expectApplicationToBeConsistent($application);
});

it('keeps applied_at when the application is applied again later', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');

    changeStatus($application, ApplicationStatus::Rejected, '2026-09-10 10:00:00');
    changeStatus($application, ApplicationStatus::Applied, '2026-09-12 10:00:00');

    expect($application->fresh()?->applied_at?->toDateTimeString())->toBe('2026-09-01 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('allows any status to follow any other', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');

    changeStatus($application, ApplicationStatus::Accepted, '2026-09-02 10:00:00');
    changeStatus($application, ApplicationStatus::Saved, '2026-09-03 10:00:00');

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Saved);

    expectApplicationToBeConsistent($application);
});

it('dates the change now when no date is given', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $this->travelTo(CarbonImmutable::parse('2026-09-10 08:30:00'));

    $event = app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Applied);

    expect($event->fresh()?->occurred_at->toDateTimeString())->toBe('2026-09-10 08:30:00');
    expectApplicationToBeConsistent($application);
});

it('accepts a change dated exactly at the latest event', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');

    changeStatus($application, ApplicationStatus::Applied, '2026-09-01 10:00:00');

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Applied);

    expectApplicationToBeConsistent($application);
});

it('rejects a change to the current status', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');

    expect(fn () => changeStatus($application, ApplicationStatus::Applied, '2026-09-02 10:00:00'))
        ->toThrow(ValidationException::class, 'already has this status');

    expect($application->events()->count())->toBe(1);
});

it('rejects a change dated before the latest event', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-05 10:00:00');

    expect(fn () => changeStatus($application, ApplicationStatus::Interviewing, '2026-09-04 10:00:00'))
        ->toThrow(ValidationException::class, 'cannot be dated before the latest event');

    expect($application->events()->count())->toBe(1);
});

it('writes nothing when updating the application fails', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');

    JobApplication::updating(function (): void {
        throw new RuntimeException('Simulated failure while updating the application.');
    });

    expect(fn () => changeStatus($application, ApplicationStatus::Applied, '2026-09-02 10:00:00'))
        ->toThrow(RuntimeException::class, 'Simulated failure');

    expect($application->events()->count())->toBe(1);

    expectApplicationToBeConsistent($application);
});

it('rejects a change dated in the future', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-21 12:00:00'));
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');

    expect(fn () => changeStatus($application, ApplicationStatus::Interviewing, '2026-09-23 10:00:00'))
        ->toThrow(ValidationException::class, 'cannot be in the future');

    expect($application->events()->count())->toBe(1);
});

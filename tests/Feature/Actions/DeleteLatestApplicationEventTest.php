<?php

use App\Actions\ChangeApplicationStatus;
use App\Actions\DeleteLatestApplicationEvent;
use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('deletes the latest event and reverts the status', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    $event = app(ChangeApplicationStatus::class)->handle(
        $application,
        ApplicationStatus::Interviewing,
        CarbonImmutable::parse('2026-09-05 10:00:00'),
    );

    app(DeleteLatestApplicationEvent::class)->handle($event);

    expect($event->fresh())->toBeNull()
        ->and($application->fresh()?->status)->toBe(ApplicationStatus::Applied);

    expectApplicationToBeConsistent($application);
});

it('clears applied_at when the only applied event is deleted', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $event = app(ChangeApplicationStatus::class)->handle(
        $application,
        ApplicationStatus::Applied,
        CarbonImmutable::parse('2026-09-03 10:00:00'),
    );

    app(DeleteLatestApplicationEvent::class)->handle($event);

    $fresh = $application->fresh();

    expect($fresh?->status)->toBe(ApplicationStatus::Saved)
        ->and($fresh?->applied_at)->toBeNull();

    expectApplicationToBeConsistent($application);
});

it('rejects deleting an event that is not the latest', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $applied = app(ChangeApplicationStatus::class)->handle(
        $application,
        ApplicationStatus::Applied,
        CarbonImmutable::parse('2026-09-03 10:00:00'),
    );
    app(ChangeApplicationStatus::class)->handle(
        $application,
        ApplicationStatus::Interviewing,
        CarbonImmutable::parse('2026-09-05 10:00:00'),
    );

    expect(fn () => app(DeleteLatestApplicationEvent::class)->handle($applied))
        ->toThrow(ValidationException::class, 'Only the latest event can be deleted');

    expect($applied->fresh())->not->toBeNull();

    expectApplicationToBeConsistent($application);
});

it('rejects deleting the creation event', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $creation = $application->events()->sole();

    expect(fn () => app(DeleteLatestApplicationEvent::class)->handle($creation))
        ->toThrow(ValidationException::class, 'creation event cannot be deleted');

    expect($creation->fresh())->not->toBeNull();
});

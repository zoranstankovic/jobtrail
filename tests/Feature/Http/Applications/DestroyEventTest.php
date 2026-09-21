<?php

use App\Actions\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;

it('undoes the latest status change', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    $interview = app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Interviewing, CarbonImmutable::parse('2026-09-05 10:00:00'));

    $this->delete("/events/{$interview->id}")
        ->assertRedirect("/postings/{$application->job_posting_id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Status change undone.']);

    expect($interview->fresh())->toBeNull()
        ->and($application->fresh()?->status)->toBe(ApplicationStatus::Applied);

    expectApplicationToBeConsistent($application);
});

it('refuses to delete an event that is not the latest', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $applied = app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Applied, CarbonImmutable::parse('2026-09-03 10:00:00'));
    app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Interviewing, CarbonImmutable::parse('2026-09-05 10:00:00'));

    $this->delete("/events/{$applied->id}")
        ->assertSessionHasErrors(['event' => 'Only the latest event can be deleted.']);

    expect($applied->fresh())->not->toBeNull();
});

it('refuses to delete the creation event', function (): void {
    $event = createApplication()->events()->sole();

    $this->delete("/events/{$event->id}")
        ->assertSessionHasErrors(['event' => 'The creation event cannot be deleted. Delete the application instead.']);

    expect($event->fresh())->not->toBeNull();
});

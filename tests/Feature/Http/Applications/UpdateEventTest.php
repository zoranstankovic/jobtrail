<?php

use App\Actions\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Carbon\CarbonImmutable;

/**
 * Saved on Sep 1, applied on Sep 3, interviewing on Sep 5; returns the
 * interviewing event.
 */
function interviewingTimeline(JobApplication $application): JobApplicationEvent
{
    app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Applied, CarbonImmutable::parse('2026-09-03 10:00:00'));

    return app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Interviewing, CarbonImmutable::parse('2026-09-05 10:00:00'));
}

it('re-dates an event and edits its note', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $interview = interviewingTimeline($application);

    $this->patch("/events/{$interview->id}", [
        'occurred_at' => '2026-09-04T10:00:00.000Z',
        'note' => 'Entered on the wrong day',
    ])
        ->assertRedirect("/postings/{$application->job_posting_id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Event updated.']);

    $event = $interview->fresh();

    expect($event?->occurred_at->toDateTimeString())->toBe('2026-09-04 10:00:00')
        ->and($event?->note)->toBe('Entered on the wrong day')
        ->and($event?->to_status)->toBe(ApplicationStatus::Interviewing)
        ->and($application->fresh()?->status)->toBe(ApplicationStatus::Interviewing);

    expectApplicationToBeConsistent($application);
});

it('rejects a date before the event it follows', function (): void {
    $application = createApplication(ApplicationStatus::Saved, '2026-09-01 10:00:00');
    $interview = interviewingTimeline($application);

    $this->patch("/events/{$interview->id}", ['occurred_at' => '2026-09-02T10:00:00.000Z'])
        ->assertSessionHasErrors(['occurred_at' => 'An event cannot be dated before the event it follows.']);

    expect($interview->fresh()?->occurred_at->toDateTimeString())->toBe('2026-09-05 10:00:00');
});

it('ignores attempts to change the statuses', function (): void {
    $application = createApplication(ApplicationStatus::Applied);
    $event = $application->events()->sole();

    $this->patch("/events/{$event->id}", [
        'occurred_at' => '2026-09-01T10:00:00.000Z',
        'to_status' => 'offer',
    ])
        ->assertRedirect("/postings/{$application->job_posting_id}")
        ->assertSessionHasNoErrors();

    expect($event->fresh()?->to_status)->toBe(ApplicationStatus::Applied);
});

it('requires a date', function (): void {
    $event = createApplication()->events()->sole();

    $this->patch("/events/{$event->id}", ['occurred_at' => '', 'note' => 'x'])
        ->assertSessionHasErrors('occurred_at');
});

<?php

use App\Enums\ApplicationStatus;

it('changes the status and records the event', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');

    $this->post("/applications/{$application->id}/events", [
        'status' => 'interviewing',
        'occurred_at' => '2026-09-04T14:00:00.000Z',
        'note' => 'First call with the team lead',
    ])
        ->assertRedirect("/postings/{$application->job_posting_id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Status changed.']);

    $latest = $application->latestEvent()->firstOrFail();

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Interviewing)
        ->and($latest->from_status)->toBe(ApplicationStatus::Applied)
        ->and($latest->occurred_at->toDateTimeString())->toBe('2026-09-04 14:00:00')
        ->and($latest->note)->toBe('First call with the team lead');

    expectApplicationToBeConsistent($application);
});

it('rejects the current status', function (): void {
    $application = createApplication(ApplicationStatus::Applied);

    $this->post("/applications/{$application->id}/events", ['status' => 'applied'])
        ->assertSessionHasErrors(['status' => 'The application already has this status.']);

    expect($application->events()->count())->toBe(1);
});

it('rejects a date before the latest event', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');

    $this->post("/applications/{$application->id}/events", [
        'status' => 'interviewing',
        'occurred_at' => '2026-08-01T10:00:00.000Z',
    ])->assertSessionHasErrors(['occurred_at' => 'A status change cannot be dated before the latest event.']);

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Applied);
});

it('rejects an unknown status', function (): void {
    $application = createApplication();

    $this->post("/applications/{$application->id}/events", ['status' => 'ghosted'])
        ->assertSessionHasErrors('status');
});

it('returns 404 for a missing application', function (): void {
    $this->post('/applications/999999/events', ['status' => 'applied'])->assertNotFound();
});

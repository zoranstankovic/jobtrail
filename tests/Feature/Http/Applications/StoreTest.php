<?php

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Models\JobPosting;
use Carbon\CarbonImmutable;

it('saves a posting for later, dated now', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-19 10:00:00'));
    $posting = JobPosting::factory()->create();

    $this->post("/postings/{$posting->id}/application", ['status' => 'saved'])
        ->assertRedirect("/postings/{$posting->id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Saved for later.']);

    $application = $posting->application()->sole();

    expect($application->status)->toBe(ApplicationStatus::Saved)
        ->and($application->events()->sole()->occurred_at->toDateTimeString())->toBe('2026-09-19 10:00:00');

    expectApplicationToBeConsistent($application);
});

it('marks a posting as applied at the given time', function (): void {
    $posting = JobPosting::factory()->create();

    $this->post("/postings/{$posting->id}/application", [
        'status' => 'applied',
        'occurred_at' => '2026-09-05T08:30:00.000Z',
    ])->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Marked as applied.']);

    $application = $posting->application()->sole();

    expect($application->status)->toBe(ApplicationStatus::Applied)
        ->and($application->applied_at?->toDateTimeString())->toBe('2026-09-05 08:30:00');

    expectApplicationToBeConsistent($application);
});

it('rejects a status other than saved or applied', function (): void {
    $posting = JobPosting::factory()->create();

    $this->post("/postings/{$posting->id}/application", ['status' => 'offer'])
        ->assertSessionHasErrors('status');

    expect($posting->application()->exists())->toBeFalse();
});

it('rejects an invalid date', function (): void {
    $posting = JobPosting::factory()->create();

    $this->post("/postings/{$posting->id}/application", ['status' => 'applied', 'occurred_at' => 'not a date'])
        ->assertSessionHasErrors('occurred_at');
});

it('rejects a second application for the same posting', function (): void {
    $posting = JobPosting::factory()->create();
    app(CreateJobApplication::class)->handle($posting);

    $this->post("/postings/{$posting->id}/application", ['status' => 'applied'])
        ->assertSessionHasErrors(['job_posting_id' => 'This posting already has an application.']);
});

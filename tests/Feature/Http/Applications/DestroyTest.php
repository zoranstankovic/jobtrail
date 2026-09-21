<?php

use App\Actions\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use App\Models\JobApplicationEvent;
use Carbon\CarbonImmutable;

it('deletes the application with its events', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Rejected, CarbonImmutable::parse('2026-09-05 10:00:00'));
    $posting = $application->jobPosting;

    $this->delete("/applications/{$application->id}")
        ->assertRedirect("/postings/{$posting->id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Application deleted.']);

    expect($application->fresh())->toBeNull()
        ->and(JobApplicationEvent::query()->where('job_application_id', $application->id)->count())->toBe(0)
        ->and($posting->fresh()?->application)->toBeNull();
});

it('returns 404 for a missing application', function (): void {
    $this->delete('/applications/999999')->assertNotFound();
});

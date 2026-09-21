<?php

use App\Actions\ChangeApplicationStatus;
use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Models\JobPosting;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

it('has no application for a posting that was not applied to', function (): void {
    $posting = JobPosting::factory()->create();

    $this->get("/postings/{$posting->id}")->assertInertia(fn (Assert $page) => $page
        ->where('application', null));
});

it('shows the application with its events, newest first', function (): void {
    $posting = JobPosting::factory()->create();
    $application = app(CreateJobApplication::class)->handle(
        $posting,
        ApplicationStatus::Saved,
        CarbonImmutable::parse('2026-09-01 10:00:00'),
        'Referral from Anna',
    );
    app(ChangeApplicationStatus::class)->handle(
        $application,
        ApplicationStatus::Applied,
        CarbonImmutable::parse('2026-09-03 09:00:00'),
        'Sent CV',
    );
    $events = $application->events()->orderBy('id')->get();

    $this->get("/postings/{$posting->id}")->assertInertia(fn (Assert $page) => $page
        ->where('application.id', $application->id)
        ->where('application.status', 'applied')
        ->where('application.applied_at', '2026-09-03T09:00:00.000000Z')
        ->where('application.notes', 'Referral from Anna')
        ->has('application.events', 2)
        ->where('application.events.0', [
            'id' => $events[1]->id,
            'from_status' => 'saved',
            'to_status' => 'applied',
            'occurred_at' => '2026-09-03T09:00:00.000000Z',
            'note' => 'Sent CV',
            'can_delete' => true,
        ])
        ->where('application.events.1.id', $events[0]->id)
        ->where('application.events.1.from_status', null)
        ->where('application.events.1.can_delete', false));
});

it('never offers to delete the creation event', function (): void {
    $application = createApplication(ApplicationStatus::Applied);

    $this->get("/postings/{$application->job_posting_id}")->assertInertia(fn (Assert $page) => $page
        ->has('application.events', 1)
        ->where('application.events.0.can_delete', false));
});

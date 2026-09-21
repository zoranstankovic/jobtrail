<?php

use App\Actions\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the Applied tab by default, oldest activity first', function (): void {
    $recent = createApplication(ApplicationStatus::Applied, '2026-09-10 10:00:00');
    $stale = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    createApplication(ApplicationStatus::Saved, '2026-09-05 10:00:00');

    $this->get('/applications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('applications/Index')
            ->where('tab', 'applied')
            ->has('applications.data', 2)
            ->where('applications.data.0', [
                'id' => $stale->id,
                'status' => 'applied',
                'applied_at' => '2026-09-01T10:00:00.000000Z',
                'last_activity_at' => '2026-09-01T10:00:00.000000Z',
                'posting' => ['id' => $stale->jobPosting->id, 'title' => $stale->jobPosting->title],
                'company' => ['id' => $stale->jobPosting->company->id, 'name' => $stale->jobPosting->company->name],
            ])
            ->where('applications.data.1.id', $recent->id));
});

it('takes the last activity from the latest event', function (): void {
    $application = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    app(ChangeApplicationStatus::class)->handle($application, ApplicationStatus::Interviewing, CarbonImmutable::parse('2026-09-12 15:00:00'));

    $this->get('/applications?tab=interviewing')->assertInertia(fn (Assert $page) => $page
        ->where('tab', 'interviewing')
        ->where('applications.data.0.last_activity_at', '2026-09-12T15:00:00.000000Z'));
});

it('groups accepted, rejected and withdrawn under Closed, newest activity first', function (): void {
    $rejected = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    app(ChangeApplicationStatus::class)->handle($rejected, ApplicationStatus::Rejected, CarbonImmutable::parse('2026-09-05 10:00:00'));
    $withdrawn = createApplication(ApplicationStatus::Applied, '2026-09-01 10:00:00');
    app(ChangeApplicationStatus::class)->handle($withdrawn, ApplicationStatus::Withdrawn, CarbonImmutable::parse('2026-09-08 10:00:00'));
    createApplication(ApplicationStatus::Applied);

    $this->get('/applications?tab=closed')->assertInertia(fn (Assert $page) => $page
        ->where('tab', 'closed')
        ->where('applications.data', fn (Collection $applications) => $applications->pluck('id')->all() === [
            $withdrawn->id,
            $rejected->id,
        ]));
});

it('counts the applications per tab', function (): void {
    createApplication(ApplicationStatus::Saved);
    createApplication(ApplicationStatus::Applied);
    createApplication(ApplicationStatus::Applied);
    $offer = createApplication(ApplicationStatus::Applied);
    app(ChangeApplicationStatus::class)->handle($offer, ApplicationStatus::Offer, CarbonImmutable::parse('2026-09-10 10:00:00'));
    $accepted = createApplication(ApplicationStatus::Applied);
    app(ChangeApplicationStatus::class)->handle($accepted, ApplicationStatus::Accepted, CarbonImmutable::parse('2026-09-10 10:00:00'));

    $this->get('/applications')->assertInertia(fn (Assert $page) => $page
        ->where('tabs', [
            ['key' => 'saved', 'label' => 'Saved', 'count' => 1],
            ['key' => 'applied', 'label' => 'Applied', 'count' => 2],
            ['key' => 'interviewing', 'label' => 'Interviewing', 'count' => 0],
            ['key' => 'offer', 'label' => 'Offer', 'count' => 1],
            ['key' => 'closed', 'label' => 'Closed', 'count' => 1],
        ]));
});

it('rejects an unknown tab', function (): void {
    $this->get('/applications?tab=bogus')->assertSessionHasErrors('tab');
});

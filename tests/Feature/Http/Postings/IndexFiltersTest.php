<?php

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\JobPosting;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the active filters with the page', function (): void {
    $this->get('/postings')->assertInertia(fn (Assert $page) => $page
        ->where('filters.search', null));
});

it('searches titles and descriptions with full-text search', function (): void {
    JobPosting::factory()->create(['title' => 'Senior Laravel Developer', 'description' => 'Backend work.']);
    JobPosting::factory()->create(['title' => 'Backend Engineer', 'description' => 'We use Laravel and Vue.']);
    JobPosting::factory()->create(['title' => 'Go Engineer', 'description' => 'Microservices.']);

    $this->get('/postings?search=laravel')->assertInertia(fn (Assert $page) => $page
        ->where('filters.search', 'laravel')
        ->where('postings.data', fn (Collection $postings) => $postings->pluck('title')->all() === [
            'Backend Engineer',
            'Senior Laravel Developer',
        ]));
});

it('understands web search syntax', function (): void {
    JobPosting::factory()->create(['title' => 'Senior Laravel Developer', 'description' => 'Backend work.']);
    JobPosting::factory()->create(['title' => 'Backend Engineer', 'description' => 'We use Laravel and Vue.']);

    $this->get('/postings?search='.urlencode('laravel -vue'))->assertInertia(fn (Assert $page) => $page
        ->where('postings.data', fn (Collection $postings) => $postings->pluck('title')->all() === [
            'Senior Laravel Developer',
        ]));
});

it('filters by application state', function (string $state, array $titles): void {
    $notApplied = JobPosting::factory()->create(['title' => 'not applied']);
    $saved = JobPosting::factory()->create(['title' => 'saved']);
    $applied = JobPosting::factory()->create(['title' => 'applied']);
    app(CreateJobApplication::class)->handle($saved, ApplicationStatus::Saved);
    app(CreateJobApplication::class)->handle($applied, ApplicationStatus::Applied);

    $this->get("/postings?application={$state}")->assertInertia(fn (Assert $page) => $page
        ->where('filters.application', $state)
        ->where('postings.data', fn (Collection $postings) => $postings->pluck('title')->all() === $titles));
})->with([
    'not applied' => ['none', ['not applied']],
    'any application' => ['any', ['applied', 'saved']],
    'a specific status' => ['saved', ['saved']],
]);

it('rejects an unknown application state', function (): void {
    $this->get('/postings?application=bogus')->assertSessionHasErrors('application');
});

it('filters by work mode, seniority and source', function (string $query, array $titles): void {
    JobPosting::factory()->create(['title' => 'remote senior xing', 'work_mode' => WorkMode::Remote, 'seniority' => Seniority::Senior, 'source' => 'xing']);
    JobPosting::factory()->create(['title' => 'remote junior linkedin', 'work_mode' => WorkMode::Remote, 'seniority' => Seniority::Junior, 'source' => 'linkedin']);
    JobPosting::factory()->create(['title' => 'onsite senior linkedin', 'work_mode' => WorkMode::Onsite, 'seniority' => Seniority::Senior, 'source' => 'linkedin']);

    $this->get("/postings?{$query}")->assertInertia(fn (Assert $page) => $page
        ->where('postings.data', fn (Collection $postings) => $postings->pluck('title')->all() === $titles));
})->with([
    'work mode' => ['work_mode=remote', ['remote junior linkedin', 'remote senior xing']],
    'seniority' => ['seniority=senior', ['onsite senior linkedin', 'remote senior xing']],
    'source' => ['source=xing', ['remote senior xing']],
    'combined' => ['work_mode=remote&source=linkedin', ['remote junior linkedin']],
]);

it('offers the sources in use as filter options', function (): void {
    JobPosting::factory()->create(['source' => 'xing']);
    JobPosting::factory()->create(['source' => 'linkedin']);
    JobPosting::factory()->create(['source' => 'xing']);

    $this->get('/postings')->assertInertia(fn (Assert $page) => $page
        ->where('sources', ['linkedin', 'xing']));
});

it('rejects an unknown work mode', function (): void {
    $this->get('/postings?work_mode=bogus')->assertSessionHasErrors('work_mode');
});

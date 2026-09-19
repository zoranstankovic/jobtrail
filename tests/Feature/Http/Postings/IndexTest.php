<?php

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;

it('lists postings newest first with their company, skills and application status', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);
    $older = JobPosting::factory()->for($company)->create([
        'title' => 'PHP Developer',
        'location' => 'Berlin',
        'work_mode' => WorkMode::Hybrid,
        'seniority' => Seniority::Senior,
        'source' => 'linkedin',
    ]);
    $older->skills()->attach([
        Skill::factory()->create(['name' => 'Vue'])->id,
        Skill::factory()->create(['name' => 'Laravel'])->id,
    ]);
    app(CreateJobApplication::class)->handle($older, ApplicationStatus::Applied);
    $newer = JobPosting::factory()->create(['title' => 'Go Developer']);

    $this->get('/postings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('postings/Index')
            ->has('postings.data', 2)
            ->where('postings.data.0.id', $newer->id)
            ->where('postings.data.0.status', null)
            ->where('postings.data.1', [
                'id' => $older->id,
                'title' => 'PHP Developer',
                'company' => ['id' => $company->id, 'name' => 'Acme GmbH'],
                'location' => 'Berlin',
                'work_mode' => 'hybrid',
                'seniority' => 'senior',
                'skills' => ['Laravel', 'Vue'],
                'source' => 'linkedin',
                'status' => 'applied',
            ]));
});

it('paginates twenty postings per page', function (): void {
    JobPosting::factory()->count(21)->create();

    $this->get('/postings?page=2')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('postings.data', 1)
            ->where('postings.current_page', 2)
            ->where('postings.last_page', 2)
            ->where('postings.total', 21));
});

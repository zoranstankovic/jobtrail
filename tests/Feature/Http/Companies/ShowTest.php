<?php

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobPosting;
use Inertia\Testing\AssertableInertia as Assert;

it('shows a company with its postings, newest first, and their application status', function (): void {
    $company = Company::factory()->create([
        'name' => 'Acme GmbH',
        'website' => 'https://acme.example',
        'city' => 'Berlin',
        'notes' => "Line one\nLine two",
    ]);
    $older = JobPosting::factory()->for($company)->create([
        'title' => 'PHP Developer',
        'created_at' => '2026-09-01 10:00:00',
    ]);
    $newer = JobPosting::factory()->for($company)->create([
        'title' => 'Vue Developer',
        'location' => 'Remote',
        'created_at' => '2026-09-02 10:00:00',
    ]);
    app(CreateJobApplication::class)->handle($older, ApplicationStatus::Applied);

    $this->get("/companies/{$company->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Show')
            ->where('company', [
                'id' => $company->id,
                'name' => 'Acme GmbH',
                'website' => 'https://acme.example',
                'city' => 'Berlin',
                'notes' => "Line one\nLine two",
            ])
            ->has('postings', 2)
            ->where('postings.0', [
                'id' => $newer->id,
                'title' => 'Vue Developer',
                'location' => 'Remote',
                'status' => null,
            ])
            ->where('postings.1.id', $older->id)
            ->where('postings.1.status', 'applied'));
});

it('returns 404 for a missing company', function (): void {
    $this->get('/companies/999999')->assertNotFound();
});

<?php

use App\Actions\CreateJobApplication;
use App\Models\Company;
use App\Models\JobPosting;
use Inertia\Testing\AssertableInertia as Assert;

it('lists companies by name with their posting and application counts', function (): void {
    $acme = Company::factory()->create(['name' => 'Acme GmbH', 'city' => 'Berlin', 'website' => 'https://acme.example', 'ats' => 'personio']);
    Company::factory()->create(['name' => 'beta AG']);
    $postings = JobPosting::factory()->count(2)->for($acme)->create();
    app(CreateJobApplication::class)->handle($postings[0]);

    $this->get('/companies')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Index')
            ->has('companies', 2)
            ->where('companies.0', [
                'id' => $acme->id,
                'name' => 'Acme GmbH',
                'city' => 'Berlin',
                'website' => 'https://acme.example',
                'ats' => 'personio',
                'postings_count' => 2,
                'applications_count' => 1,
            ])
            ->where('companies.1.name', 'beta AG')
            ->where('companies.1.postings_count', 0));
});

it('renders an empty list when there are no companies', function (): void {
    $this->get('/companies')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Index')
            ->has('companies', 0));
});

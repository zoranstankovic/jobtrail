<?php

use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Skill;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

it('shows every field of a posting', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00'));

    $company = Company::factory()->create(['name' => 'Acme GmbH']);
    $posting = JobPosting::factory()->for($company)->create([
        'title' => 'Senior Laravel Developer',
        'url' => 'https://jobs.example/laravel',
        'source' => 'linkedin',
        'location' => 'Berlin',
        'work_mode' => WorkMode::Hybrid,
        'employment_type' => EmploymentType::FullTime,
        'seniority' => Seniority::Senior,
        'salary_min' => 60000,
        'salary_max' => 75000,
        'salary_currency' => 'EUR',
        'salary_period' => SalaryPeriod::Yearly,
        'description' => "Line one\nLine two",
        'posted_at' => '2026-09-01',
    ]);
    $posting->skills()->attach([
        Skill::factory()->create(['name' => 'vue'])->id,
        Skill::factory()->create(['name' => 'Laravel'])->id,
    ]);

    $this->get("/postings/{$posting->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('postings/Show')
            ->where('posting', [
                'id' => $posting->id,
                'title' => 'Senior Laravel Developer',
                'url' => 'https://jobs.example/laravel',
                'source' => 'linkedin',
                'location' => 'Berlin',
                'work_mode' => 'hybrid',
                'employment_type' => 'full_time',
                'seniority' => 'senior',
                'salary_min' => 60000,
                'salary_max' => 75000,
                'salary_currency' => 'EUR',
                'salary_period' => 'yearly',
                'description' => "Line one\nLine two",
                'posted_at' => '2026-09-01T00:00:00.000000Z',
                'created_at' => '2026-09-10T12:00:00.000000Z',
                'company' => ['id' => $company->id, 'name' => 'Acme GmbH'],
                'skills' => ['Laravel', 'vue'],
            ]));
});

it('returns 404 for a missing posting', function (): void {
    $this->get('/postings/999999')->assertNotFound();
});

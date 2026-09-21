<?php

use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the create form with company and source suggestions', function (): void {
    Company::factory()->create(['name' => 'beta AG']);
    $acme = Company::factory()->create(['name' => 'Acme GmbH']);
    JobPosting::factory()->for($acme)->create(['source' => 'glassdoor']);

    $this->get('/postings/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('postings/Create')
            ->where('companies', ['Acme GmbH', 'beta AG'])
            ->where('sources', ['arbeitnow', 'company_website', 'glassdoor', 'indeed', 'linkedin', 'referral', 'stepstone', 'xing']));
});

it('creates a posting for an existing company, matched case-insensitively', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);

    $response = $this->post('/postings', postingInput(['company' => 'acme gmbh']));

    $posting = JobPosting::query()->sole();

    $response
        ->assertRedirect("/postings/{$posting->id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Job posting created.']);

    expect($posting->company_id)->toBe($company->id)
        ->and(Company::query()->count())->toBe(1)
        ->and($posting->title)->toBe('Senior Laravel Developer')
        ->and($posting->work_mode)->toBe(WorkMode::Hybrid)
        ->and($posting->salary_max)->toBe(75000)
        ->and($posting->posted_at?->toDateString())->toBe('2026-09-01')
        ->and($posting->description)->toBe("First line\nSecond line");
});

it('creates the company inline when it does not exist yet', function (): void {
    $this->post('/postings', postingInput(['company' => 'Nordlicht Software GmbH']))
        ->assertSessionHasNoErrors();

    expect(Company::query()->sole()->name)->toBe('Nordlicht Software GmbH')
        ->and(JobPosting::query()->sole()->company->name)->toBe('Nordlicht Software GmbH');
});

it('accepts a maximum salary without a minimum', function (): void {
    $this->post('/postings', postingInput(['salary_min' => null, 'salary_max' => 70000]))
        ->assertSessionHasNoErrors();
});

it('rejects invalid input', function (array $overrides, string $field): void {
    $this->post('/postings', postingInput($overrides))->assertSessionHasErrors($field);

    expect(JobPosting::query()->count())->toBe(0);
})->with([
    'missing company' => [['company' => ''], 'company'],
    'missing title' => [['title' => ''], 'title'],
    'missing source' => [['source' => ''], 'source'],
    'invalid url' => [['url' => 'not a url'], 'url'],
    'unknown work mode' => [['work_mode' => 'office'], 'work_mode'],
    'unknown employment type' => [['employment_type' => 'gig'], 'employment_type'],
    'unknown seniority' => [['seniority' => 'guru'], 'seniority'],
    'negative salary' => [['salary_min' => -1], 'salary_min'],
    'maximum below minimum' => [['salary_min' => 80000, 'salary_max' => 60000], 'salary_max'],
    'lowercase currency' => [['salary_currency' => 'eur'], 'salary_currency'],
    'unknown salary period' => [['salary_period' => 'weekly'], 'salary_period'],
    'invalid posted date' => [['posted_at' => 'not a date'], 'posted_at'],
]);

it('rejects a URL another posting has, ignoring case', function (): void {
    JobPosting::factory()->create(['url' => 'https://jobs.example/laravel']);

    $this->post('/postings', postingInput(['url' => 'https://jobs.example/LARAVEL']))
        ->assertSessionHasErrors(['url' => 'The url has already been taken.']);
});

it('attaches new and existing skills, matching existing ones case-insensitively', function (): void {
    Skill::factory()->create(['name' => 'PostgreSQL']);

    $this->post('/postings', postingInput(['skills' => ['postgresql', 'Rust']]))
        ->assertSessionHasNoErrors();

    expect(JobPosting::query()->sole()->skills()->orderBy('name')->pluck('name')->all())
        ->toBe(['PostgreSQL', 'Rust'])
        ->and(Skill::query()->count())->toBe(2);
});

it('offers the existing skills in the create form', function (): void {
    Skill::factory()->create(['name' => 'vue']);
    Skill::factory()->create(['name' => 'Laravel']);

    $this->get('/postings/create')->assertInertia(fn (Assert $page) => $page
        ->where('skills', ['Laravel', 'vue']));
});

it('rejects invalid skills', function (mixed $skills): void {
    $this->post('/postings', postingInput(['skills' => $skills]))->assertSessionHasErrors();

    expect(JobPosting::query()->count())->toBe(0);
})->with([
    'not a list' => ['PHP'],
    'too long' => [[str_repeat('a', 101)]],
]);

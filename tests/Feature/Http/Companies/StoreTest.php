<?php

use App\Models\Company;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the create form with ATS suggestions', function (): void {
    $this->get('/companies/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Create')
            ->where('atsSuggestions', Company::ATS_SUGGESTIONS));
});

it('creates a company and redirects to it', function (): void {
    $response = $this->post('/companies', [
        'name' => 'Acme GmbH',
        'website' => 'https://acme.example',
        'city' => 'Berlin',
        'notes' => 'Great team',
    ]);

    $company = Company::query()->sole();

    $response
        ->assertRedirect("/companies/{$company->id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Company created.']);

    expect($company->only(['name', 'website', 'city', 'notes']))->toBe([
        'name' => 'Acme GmbH',
        'website' => 'https://acme.example',
        'city' => 'Berlin',
        'notes' => 'Great team',
    ]);
});

it('stores empty optional fields as null', function (): void {
    $this->post('/companies', [
        'name' => 'Acme GmbH', 'website' => '', 'city' => '', 'notes' => '',
        'careers_url' => '', 'ats' => '', 'ats_jobs_url' => '',
    ]);

    expect(Company::query()->sole()->only(['website', 'city', 'notes', 'careers_url', 'ats', 'ats_jobs_url']))
        ->toBe(['website' => null, 'city' => null, 'notes' => null, 'careers_url' => null, 'ats' => null, 'ats_jobs_url' => null]);
});

it('rejects invalid input', function (array $input, string $field): void {
    $this->post('/companies', $input)->assertSessionHasErrors($field);

    expect(Company::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'invalid website' => [['name' => 'Acme GmbH', 'website' => 'not a url'], 'website'],
]);

it('rejects a name that exists with different casing', function (): void {
    Company::factory()->create(['name' => 'Acme GmbH']);

    $this->post('/companies', ['name' => 'ACME GMBH'])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

    expect(Company::query()->count())->toBe(1);
});

it('rejects a name that differs only in inner whitespace', function (): void {
    Company::factory()->create(['name' => 'Test Company']);

    $this->post('/companies', ['name' => 'Test  Company'])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

    expect(Company::query()->count())->toBe(1);
});

it('stores the careers page and the ATS', function (): void {
    $this->post('/companies', [
        'name' => 'Acme GmbH',
        'careers_url' => 'https://acme.example/careers',
        'ats' => ' Personio ',
        'ats_jobs_url' => 'https://acme.jobs.personio.example',
    ])->assertRedirect();

    expect(Company::query()->sole()->only(['careers_url', 'ats', 'ats_jobs_url']))->toBe([
        'careers_url' => 'https://acme.example/careers',
        'ats' => 'personio',
        'ats_jobs_url' => 'https://acme.jobs.personio.example',
    ]);
});

it('rejects invalid careers fields', function (array $input, string $field): void {
    $this->post('/companies', ['name' => 'Acme GmbH', ...$input])->assertSessionHasErrors($field);

    expect(Company::query()->count())->toBe(0);
})->with([
    'invalid careers url' => [['careers_url' => 'careers page'], 'careers_url'],
    'invalid ats jobs url' => [['ats_jobs_url' => 'personio'], 'ats_jobs_url'],
    'ats too long' => [['ats' => str_repeat('a', 51)], 'ats'],
]);

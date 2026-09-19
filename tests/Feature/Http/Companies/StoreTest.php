<?php

use App\Models\Company;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the create form', function (): void {
    $this->get('/companies/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('companies/Create'));
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
    $this->post('/companies', ['name' => 'Acme GmbH', 'website' => '', 'city' => '', 'notes' => '']);

    expect(Company::query()->sole()->only(['website', 'city', 'notes']))
        ->toBe(['website' => null, 'city' => null, 'notes' => null]);
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

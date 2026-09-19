<?php

use App\Models\Company;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the edit form with the company', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH', 'website' => null, 'city' => 'Berlin', 'notes' => null]);

    $this->get("/companies/{$company->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Edit')
            ->where('company', [
                'id' => $company->id,
                'name' => 'Acme GmbH',
                'website' => null,
                'city' => 'Berlin',
                'notes' => null,
            ]));
});

it('updates a company and redirects to it', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);

    $this->put("/companies/{$company->id}", [
        'name' => 'Acme Software GmbH',
        'website' => 'https://acme.example',
        'city' => 'Hamburg',
        'notes' => 'Moved',
    ])
        ->assertRedirect("/companies/{$company->id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Company updated.']);

    expect($company->fresh()?->only(['name', 'website', 'city', 'notes']))->toBe([
        'name' => 'Acme Software GmbH',
        'website' => 'https://acme.example',
        'city' => 'Hamburg',
        'notes' => 'Moved',
    ]);
});

it('allows keeping the own name with different casing', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);

    $this->put("/companies/{$company->id}", ['name' => 'ACME GmbH'])
        ->assertSessionHasNoErrors();

    expect($company->fresh()?->name)->toBe('ACME GmbH');
});

it('rejects the name of another company', function (): void {
    Company::factory()->create(['name' => 'Nordlicht Software GmbH']);
    $company = Company::factory()->create(['name' => 'Acme GmbH']);

    $this->put("/companies/{$company->id}", ['name' => 'nordlicht software gmbh'])
        ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

    expect($company->fresh()?->name)->toBe('Acme GmbH');
});

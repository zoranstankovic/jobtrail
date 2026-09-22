<?php

use App\Actions\ResolveCompany;
use App\Models\Company;

it('returns the existing company, matched case-insensitively after trimming', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);

    $resolved = app(ResolveCompany::class)->handle('  acme GMBH ');

    expect($resolved->is($company))->toBeTrue()
        ->and(Company::query()->count())->toBe(1);
});

it('creates a company with the trimmed name when none matches', function (): void {
    $company = app(ResolveCompany::class)->handle('  Nordlicht Software GmbH ');

    expect($company->exists)->toBeTrue()
        ->and($company->name)->toBe('Nordlicht Software GmbH');
});

it('returns the existing company when the name differs only in inner whitespace', function (): void {
    $company = Company::factory()->create(['name' => 'Test Company']);

    $resolved = app(ResolveCompany::class)->handle('Test  Company');

    expect($resolved->is($company))->toBeTrue()
        ->and(Company::query()->count())->toBe(1);
});

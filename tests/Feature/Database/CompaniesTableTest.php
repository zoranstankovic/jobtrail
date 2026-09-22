<?php

use App\Models\Company;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('stores a company', function (): void {
    $company = Company::factory()->create([
        'name' => 'Nordlicht Software GmbH',
        'city' => 'Hamburg',
    ]);

    expect($company->fresh()?->name)->toBe('Nordlicht Software GmbH')
        ->and($company->fresh()?->city)->toBe('Hamburg');
});

it('uses timestamptz for the timestamps', function (): void {
    $types = DB::table('information_schema.columns')
        ->where('table_name', 'companies')
        ->whereIn('column_name', ['created_at', 'updated_at'])
        ->pluck('data_type')
        ->unique()
        ->all();

    expect($types)->toBe(['timestamp with time zone']);
});

it('rejects a company name that differs only in letter case', function (): void {
    Company::factory()->create(['name' => 'Nordlicht Software GmbH']);

    // The violating insert must be the last database call: after an error
    // PostgreSQL aborts the surrounding test transaction.
    expect(fn () => Company::factory()->create(['name' => 'NORDLICHT software gmbh']))
        ->toThrow(UniqueConstraintViolationException::class, 'companies_name_lower_unique');
});

it('stores the ATS lowercase with whitespace collapsed', function (): void {
    $company = Company::factory()->create(['ats' => '  Smart  Recruiters ']);

    expect($company->fresh()?->ats)->toBe('smart recruiters');
});

it('suggests the known ATS merged with the ones in use, sorted', function (): void {
    Company::factory()->create(['ats' => 'Taleo']);
    Company::factory()->create(['ats' => 'personio']);
    Company::factory()->create(['ats' => null]);

    expect(Company::atsSuggestions())->toBe([
        'dvinci', 'greenhouse', 'join', 'lever', 'personio', 'recruitee',
        'rexx', 'smartrecruiters', 'softgarden', 'successfactors', 'taleo', 'workday',
    ]);
});

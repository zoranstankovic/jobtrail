<?php

use App\Models\Company;
use App\Rules\UniqueCaseInsensitive;
use Illuminate\Support\Facades\Validator;

it('rejects a value that exists with different casing and spacing', function (): void {
    Company::factory()->create(['name' => 'Acme GmbH']);

    $validator = Validator::make(
        ['name' => ' ACME gmbh '],
        ['name' => [new UniqueCaseInsensitive('companies', 'name')]],
    );

    expect($validator->errors()->first('name'))->toBe('The name has already been taken.');
});

it('accepts a value nobody has', function (): void {
    Company::factory()->create(['name' => 'Acme GmbH']);

    $validator = Validator::make(
        ['name' => 'Nordlicht Software GmbH'],
        ['name' => [new UniqueCaseInsensitive('companies', 'name')]],
    );

    expect($validator->passes())->toBeTrue();
});

it('ignores the row being edited', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);

    $validator = Validator::make(
        ['name' => 'ACME GmbH'],
        ['name' => [new UniqueCaseInsensitive('companies', 'name', $company->id)]],
    );

    expect($validator->passes())->toBeTrue();
});

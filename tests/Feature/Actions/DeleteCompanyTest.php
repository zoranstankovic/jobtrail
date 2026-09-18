<?php

use App\Actions\DeleteCompany;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Validation\ValidationException;

it('deletes a company without postings', function (): void {
    $company = Company::factory()->create();

    app(DeleteCompany::class)->handle($company);

    expect($company->fresh())->toBeNull();
});

it('refuses to delete a company that has postings', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->for($company)->create();

    expect(fn () => app(DeleteCompany::class)->handle($company))
        ->toThrow(ValidationException::class, 'still has job postings');

    expect($company->fresh())->not->toBeNull();
});

<?php

use App\Models\Company;
use App\Models\JobPosting;

it('deletes a company without postings', function (): void {
    $company = Company::factory()->create();

    $this->delete("/companies/{$company->id}")
        ->assertRedirect('/companies')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Company deleted.']);

    expect($company->fresh())->toBeNull();
});

it('refuses to delete a company that has postings', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->for($company)->create();

    $this->from("/companies/{$company->id}")
        ->delete("/companies/{$company->id}")
        ->assertRedirect("/companies/{$company->id}")
        ->assertSessionHasErrors(['company' => 'This company still has job postings. Delete them first.']);

    expect($company->fresh())->not->toBeNull();
});

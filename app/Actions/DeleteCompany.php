<?php

namespace App\Actions;

use App\Models\Company;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a company, but only when no postings reference it
 * (docs/design.md §5.7). The foreign key's ON DELETE RESTRICT backs this up.
 */
final class DeleteCompany
{
    public function handle(Company $company): void
    {
        if ($company->jobPostings()->exists()) {
            throw ValidationException::withMessages([
                'company' => 'This company still has job postings. Delete them first.',
            ]);
        }

        $company->delete();
    }
}

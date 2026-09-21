<?php

namespace App\Actions;

use App\Models\Company;

/**
 * Turns a company name typed into the posting form into a Company: an
 * existing one, matched case-insensitively, or a new one
 * (docs/design.md §6.2).
 */
final class ResolveCompany
{
    public function handle(string $name): Company
    {
        $name = trim($name);

        // Same shape as the companies_name_lower_unique index, so the lookup
        // is served by that index.
        return Company::query()->whereRaw('lower(name) = lower(?)', [$name])->first()
            ?? Company::query()->create(['name' => $name]);
    }
}

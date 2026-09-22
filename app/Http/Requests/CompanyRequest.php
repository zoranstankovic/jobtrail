<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Rules\UniqueCaseInsensitive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Validates the company form, for both create and edit
 * (docs/design.md §5 "Error handling").
 */
class CompanyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Set on edit (PUT /companies/{company}), null on create.
        $company = $this->route('company');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueCaseInsensitive('companies', 'name', $company instanceof Company ? $company->id : null),
            ],
            'website' => ['nullable', 'url', 'max:2048'],
            'city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'careers_url' => ['nullable', 'url', 'max:2048'],
            'ats' => ['nullable', 'string', 'max:50'],
            'ats_jobs_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * Collapse runs of whitespace in the name, so "Acme  GmbH" is stored and
     * checked for uniqueness as "Acme GmbH" and cannot sneak in as a
     * duplicate that renders identically.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => Str::squish($name)]);
        }
    }
}

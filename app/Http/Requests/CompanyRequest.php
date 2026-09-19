<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Rules\UniqueCaseInsensitive;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}

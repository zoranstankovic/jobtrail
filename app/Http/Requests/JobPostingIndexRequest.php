<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the postings index query string (docs/design.md §6.1).
 */
class JobPostingIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'application' => ['nullable', Rule::in([
                'none',
                'any',
                ...array_map(fn (ApplicationStatus $status): string => $status->value, ApplicationStatus::cases()),
            ])],
        ];
    }

    /**
     * Every known filter, null when unset, so the page can bind its inputs
     * to all of them.
     *
     * @return array<string, string|null>
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'application' => $this->validated('application'),
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use App\Http\Requests\Concerns\ParsesOccurredAt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Save for later" and "Mark as applied" on the posting page
 * (docs/design.md §5.1, §6.3).
 */
class StoreJobApplicationRequest extends FormRequest
{
    use ParsesOccurredAt;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([ApplicationStatus::Saved->value, ApplicationStatus::Applied->value])],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}

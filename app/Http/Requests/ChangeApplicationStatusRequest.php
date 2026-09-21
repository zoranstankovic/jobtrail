<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use App\Http\Requests\Concerns\ParsesOccurredAt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Change status" dialog (docs/design.md §5.2, §6.3).
 */
class ChangeApplicationStatusRequest extends FormRequest
{
    use ParsesOccurredAt;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}

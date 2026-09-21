<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the applications index tab (docs/design.md §6.4).
 */
class JobApplicationIndexRequest extends FormRequest
{
    public const TABS = ['saved', 'applied', 'interviewing', 'offer', 'closed'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(self::TABS)],
        ];
    }

    /**
     * The selected tab; Applied by default, where follow-ups matter.
     */
    public function tab(): string
    {
        return $this->string('tab')->value() ?: 'applied';
    }
}

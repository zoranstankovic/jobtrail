<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The posting form on create, which also offers "I already applied"
 * (docs/design.md §6.2).
 */
class StoreJobPostingRequest extends JobPostingRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'already_applied' => ['boolean'],
            'applied_at' => ['nullable', 'date'],
        ];
    }

    /**
     * When "I already applied" is checked: the application time in UTC, or
     * now when none was given. Null when it is not checked.
     */
    public function appliedAt(): ?CarbonInterface
    {
        if (! $this->boolean('already_applied')) {
            return null;
        }

        return $this->date('applied_at')?->utc() ?? CarbonImmutable::now();
    }
}

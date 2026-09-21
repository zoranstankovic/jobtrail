<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing an event on the timeline: only its date and note
 * (docs/design.md §5.3).
 */
class UpdateApplicationEventRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'occurred_at' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * The new date in UTC. "required" has already rejected an empty value.
     */
    public function occurredAt(): CarbonInterface
    {
        return CarbonImmutable::parse($this->string('occurred_at')->value())->utc();
    }
}

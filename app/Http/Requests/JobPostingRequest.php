<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\JobPosting;
use App\Rules\UniqueCaseInsensitive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the posting form (docs/design.md §5 "Error handling", §6.2).
 */
class JobPostingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Set on edit (PUT /postings/{posting}), null on create.
        $posting = $this->route('posting');

        return [
            // A name: an existing company (any casing) or a new one.
            'company' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'url' => [
                'nullable',
                'url',
                'max:2048',
                new UniqueCaseInsensitive('job_postings', 'url', $posting instanceof JobPosting ? $posting->id : null),
            ],
            'source' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'work_mode' => ['nullable', Rule::enum(WorkMode::class)],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'seniority' => ['nullable', Rule::enum(Seniority::class)],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            // gte fails when salary_min is empty, so it only applies when set.
            'salary_max' => ['nullable', 'integer', 'min:0', Rule::when($this->filled('salary_min'), 'gte:salary_min')],
            'salary_currency' => ['required', 'string', 'size:3', 'alpha', 'uppercase'],
            'salary_period' => ['nullable', Rule::enum(SalaryPeriod::class)],
            'description' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
            'skills' => ['array'],
            'skills.*' => ['string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'salary_max.gte' => 'The maximum salary must be at least the minimum salary.',
        ];
    }

    /**
     * The posting's own columns, ready for JobPosting::fill(). The company,
     * skills and "already applied" fields are handled separately.
     *
     * @return array<string, mixed>
     */
    public function postingAttributes(): array
    {
        return $this->safe()->except(['company', 'skills', 'already_applied', 'applied_at']);
    }

    /**
     * The typed skill names; ResolveSkills trims and deduplicates them.
     *
     * @return list<string>
     */
    public function skillNames(): array
    {
        /** @var list<string> $names */
        $names = array_values($this->validated('skills', []));

        return $names;
    }
}

<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Support\Facades\DB;

/**
 * Saves the posting form on edit: the posting's fields, its company (existing
 * or created inline) and its skills (docs/design.md §6.2). The application is
 * never touched here.
 */
final class UpdateJobPosting
{
    public function __construct(
        private readonly ResolveCompany $resolveCompany,
        private readonly ResolveSkills $resolveSkills,
    ) {}

    /**
     * @param  Company|string  $company  an existing company, or the name of one to find or create
     * @param  array<string, mixed>  $attributes  the posting's fillable fields
     * @param  list<string>  $skillNames  the complete new list of skills
     */
    public function handle(
        JobPosting $posting,
        Company|string $company,
        array $attributes,
        array $skillNames = [],
    ): JobPosting {
        return DB::transaction(function () use ($posting, $company, $attributes, $skillNames): JobPosting {
            $company = is_string($company) ? $this->resolveCompany->handle($company) : $company;

            $posting->company()->associate($company);
            $posting->fill($attributes)->save();

            $posting->skills()->sync($this->resolveSkills->handle($skillNames)->modelKeys());

            return $posting;
        });
    }
}

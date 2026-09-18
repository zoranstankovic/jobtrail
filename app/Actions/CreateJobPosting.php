<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobPosting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Creates a posting from the posting form: optionally with a new company,
 * with skills created on the fly, and optionally already applied to
 * (docs/design.md §6.2).
 */
final class CreateJobPosting
{
    public function __construct(
        private readonly ResolveSkills $resolveSkills,
        private readonly CreateJobApplication $createJobApplication,
    ) {}

    /**
     * @param  Company|string  $company  an existing company, or the name of one to create
     * @param  array<string, mixed>  $attributes  the posting's fillable fields
     * @param  list<string>  $skillNames
     * @param  CarbonInterface|null  $appliedAt  set when "I already applied" is checked
     */
    public function handle(
        Company|string $company,
        array $attributes,
        array $skillNames = [],
        ?CarbonInterface $appliedAt = null,
    ): JobPosting {
        return DB::transaction(function () use ($company, $attributes, $skillNames, $appliedAt): JobPosting {
            $company = is_string($company) ? $this->findOrCreateCompany($company) : $company;

            $posting = $company->jobPostings()->create($attributes);

            $posting->skills()->sync($this->resolveSkills->handle($skillNames)->modelKeys());

            if ($appliedAt !== null) {
                $this->createJobApplication->handle($posting, ApplicationStatus::Applied, $appliedAt);
            }

            return $posting;
        });
    }

    private function findOrCreateCompany(string $name): Company
    {
        $name = trim($name);

        return Company::query()->whereRaw('lower(name) = lower(?)', [$name])->first()
            ?? Company::query()->create(['name' => $name]);
    }
}

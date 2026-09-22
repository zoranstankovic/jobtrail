<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $website
 * @property string|null $city
 * @property string|null $notes
 * @property string|null $careers_url
 * @property string|null $ats
 * @property string|null $ats_jobs_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, JobPosting> $jobPostings
 * @property-read Collection<int, JobApplication> $jobApplications
 * @property-read int|null $job_postings_count
 * @property-read int|null $job_applications_count
 */
#[Fillable(['name', 'website', 'city', 'notes', 'careers_url', 'ats', 'ats_jobs_url'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * Applicant tracking systems common in the German market; offered as
     * suggestions next to the ones already in use (docs/design.md §4.2).
     *
     * @var list<string>
     */
    public const ATS_SUGGESTIONS = [
        'dvinci',
        'greenhouse',
        'join',
        'lever',
        'personio',
        'recruitee',
        'rexx',
        'smartrecruiters',
        'softgarden',
        'successfactors',
        'workday',
    ];

    /**
     * The fixed suggestions merged with the ATS values in use, sorted. New
     * values stay allowed; these are only suggestions.
     *
     * @return array<int, string>
     */
    public static function atsSuggestions(): array
    {
        return self::query()
            ->whereNotNull('ats')
            ->distinct()
            ->pluck('ats')
            ->merge(self::ATS_SUGGESTIONS)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return HasMany<JobPosting, $this>
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * The applications of this company's postings.
     *
     * @return HasManyThrough<JobApplication, JobPosting, $this>
     */
    public function jobApplications(): HasManyThrough
    {
        return $this->hasManyThrough(JobApplication::class, JobPosting::class);
    }

    /**
     * Stored lowercase with whitespace collapsed, so "Personio" and
     * "personio" count as one ATS.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function ats(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null ? null : Str::lower(Str::squish($value)),
        );
    }
}

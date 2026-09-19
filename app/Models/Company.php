<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string|null $website
 * @property string|null $city
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, JobPosting> $jobPostings
 * @property-read Collection<int, JobApplication> $jobApplications
 * @property-read int|null $job_postings_count
 * @property-read int|null $job_applications_count
 */
#[Fillable(['name', 'website', 'city', 'notes'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

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
}

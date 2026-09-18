<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use Carbon\CarbonImmutable;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $company_id
 * @property string $title
 * @property string|null $url
 * @property string $source
 * @property string|null $connector
 * @property string|null $external_id
 * @property string|null $location
 * @property WorkMode|null $work_mode
 * @property EmploymentType|null $employment_type
 * @property Seniority|null $seniority
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property string $salary_currency
 * @property SalaryPeriod|null $salary_period
 * @property string|null $description
 * @property array<mixed>|null $raw_payload
 * @property CarbonImmutable|null $posted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, Skill> $skills
 */
#[Fillable([
    'title',
    'url',
    'source',
    'location',
    'work_mode',
    'employment_type',
    'seniority',
    'salary_min',
    'salary_max',
    'salary_currency',
    'salary_period',
    'description',
    'posted_at',
])]
#[Hidden(['search_vector'])]
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    /**
     * The PostgreSQL text search configuration behind search_vector.
     * "simple" does no stemming, because postings mix English and German
     * (docs/design.md §4.3).
     */
    public const SEARCH_CONFIG = 'simple';

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    /**
     * Sources are stored trimmed and lowercase, so "LinkedIn" and "linkedin"
     * count as one source.
     *
     * @return Attribute<string, string>
     */
    protected function source(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(trim($value)),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_mode' => WorkMode::class,
            'employment_type' => EmploymentType::class,
            'seniority' => Seniority::class,
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'salary_period' => SalaryPeriod::class,
            'raw_payload' => 'array',
            'posted_at' => 'immutable_datetime',
        ];
    }
}

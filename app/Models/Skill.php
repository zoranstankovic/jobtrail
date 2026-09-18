<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, JobPosting> $jobPostings
 */
#[Fillable(['name'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<JobPosting, $this>
     */
    public function jobPostings(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class);
    }
}

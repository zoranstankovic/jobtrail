<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $job_posting_id
 * @property ApplicationStatus $status
 * @property CarbonImmutable|null $applied_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read JobPosting $jobPosting
 * @property-read Collection<int, JobApplicationEvent> $events
 * @property-read JobApplicationEvent|null $latestEvent
 */
#[Fillable(['status', 'applied_at', 'notes'])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return HasMany<JobApplicationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(JobApplicationEvent::class);
    }

    /**
     * The latest event: greatest occurred_at, ties broken by the greatest id
     * (docs/design.md §4.6).
     *
     * @return HasOne<JobApplicationEvent, $this>
     */
    public function latestEvent(): HasOne
    {
        return $this->hasOne(JobApplicationEvent::class)->ofMany([
            'occurred_at' => 'max',
            'id' => 'max',
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'immutable_datetime',
        ];
    }
}

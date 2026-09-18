<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\JobApplicationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $job_application_id
 * @property ApplicationStatus|null $from_status
 * @property ApplicationStatus $to_status
 * @property CarbonImmutable $occurred_at
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property-read JobApplication $jobApplication
 */
#[Fillable(['from_status', 'to_status', 'occurred_at', 'note'])]
class JobApplicationEvent extends Model
{
    /** @use HasFactory<JobApplicationEventFactory> */
    use HasFactory;

    /**
     * Events only record when they were written, not when they were edited.
     */
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<JobApplication, $this>
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}

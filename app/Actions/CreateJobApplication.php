<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates an application together with its creation event
 * (docs/design.md §5.1).
 */
final class CreateJobApplication
{
    public function handle(
        JobPosting $posting,
        ApplicationStatus $status = ApplicationStatus::Saved,
        ?CarbonInterface $occurredAt = null,
        ?string $notes = null,
    ): JobApplication {
        if (! $status->canBeInitial()) {
            throw ValidationException::withMessages([
                'status' => 'A new application must start as saved or applied.',
            ]);
        }

        if ($posting->application()->exists()) {
            throw ValidationException::withMessages([
                'job_posting_id' => 'This posting already has an application.',
            ]);
        }

        $occurredAt ??= CarbonImmutable::now();

        return DB::transaction(function () use ($posting, $status, $occurredAt, $notes): JobApplication {
            $application = $posting->application()->create([
                'status' => $status,
                'applied_at' => $status === ApplicationStatus::Applied ? $occurredAt : null,
                'notes' => $notes,
            ]);

            $application->events()->create([
                'from_status' => null,
                'to_status' => $status,
                'occurred_at' => $occurredAt,
            ]);

            return $application;
        });
    }
}

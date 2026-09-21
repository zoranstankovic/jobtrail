<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Support\EventTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves an application to a new status and records the change
 * (docs/design.md §5.2).
 */
final class ChangeApplicationStatus
{
    public function handle(
        JobApplication $application,
        ApplicationStatus $status,
        ?CarbonInterface $occurredAt = null,
        ?string $note = null,
    ): JobApplicationEvent {
        $occurredAt ??= CarbonImmutable::now();

        if ($application->status === $status) {
            throw ValidationException::withMessages([
                'status' => 'The application already has this status.',
            ]);
        }

        EventTime::ensureNotInFuture($occurredAt);

        // Backdating behind the latest event would leave the new event
        // "not latest", so the status would silently not change. Older events
        // are re-dated through UpdateApplicationEvent instead.
        $latest = $application->latestEvent()->first();

        if ($latest !== null && $occurredAt->lessThan($latest->occurred_at)) {
            throw ValidationException::withMessages([
                'occurred_at' => 'A status change cannot be dated before the latest event.',
            ]);
        }

        return DB::transaction(function () use ($application, $status, $occurredAt, $note): JobApplicationEvent {
            $event = $application->events()->create([
                'from_status' => $application->status,
                'to_status' => $status,
                'occurred_at' => $occurredAt,
                'note' => $note,
            ]);

            $application->syncStatusFromEvents();

            return $event;
        });
    }
}

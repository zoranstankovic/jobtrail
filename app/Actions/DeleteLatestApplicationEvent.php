<?php

namespace App\Actions;

use App\Models\JobApplicationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Undoes the most recent status change (docs/design.md §5.4).
 */
final class DeleteLatestApplicationEvent
{
    public function handle(JobApplicationEvent $event): void
    {
        if ($event->from_status === null) {
            throw ValidationException::withMessages([
                'event' => 'The creation event cannot be deleted. Delete the application instead.',
            ]);
        }

        $application = $event->jobApplication;
        $latest = $application->latestEvent()->first();

        if ($latest === null || $latest->isNot($event)) {
            throw ValidationException::withMessages([
                'event' => 'Only the latest event can be deleted.',
            ]);
        }

        DB::transaction(function () use ($event, $application): void {
            $event->delete();

            $application->syncStatusFromEvents();
        });
    }
}

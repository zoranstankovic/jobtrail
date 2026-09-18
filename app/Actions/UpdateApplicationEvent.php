<?php

namespace App\Actions;

use App\Models\JobApplicationEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Edits an event's date and note. The statuses are immutable, so this
 * Action does not accept them (docs/design.md §5.3).
 */
final class UpdateApplicationEvent
{
    public function handle(JobApplicationEvent $event, CarbonInterface $occurredAt, ?string $note): JobApplicationEvent
    {
        return DB::transaction(function () use ($event, $occurredAt, $note): JobApplicationEvent {
            $event->update([
                'occurred_at' => $occurredAt,
                'note' => $note,
            ]);

            // A new date can change which event is the latest, or which
            // "applied" event is the earliest.
            $event->jobApplication->syncStatusFromEvents();

            return $event;
        });
    }
}

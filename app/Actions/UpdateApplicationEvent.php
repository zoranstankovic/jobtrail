<?php

namespace App\Actions;

use App\Models\JobApplicationEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Edits an event's date and note. The statuses are immutable, so this
 * Action does not accept them (docs/design.md §5.3).
 */
final class UpdateApplicationEvent
{
    public function handle(JobApplicationEvent $event, CarbonInterface $occurredAt, ?string $note): JobApplicationEvent
    {
        $this->ensureOrderIsKept($event, $occurredAt);

        return DB::transaction(function () use ($event, $occurredAt, $note): JobApplicationEvent {
            $event->update([
                'occurred_at' => $occurredAt,
                'note' => $note,
            ]);

            // The order is kept, but a moved "applied" event can change
            // applied_at.
            $event->jobApplication->syncStatusFromEvents();

            return $event;
        });
    }

    /**
     * The statuses are immutable, so the event must keep its place: not
     * before the event it follows, not after the one that follows it. Equal
     * times keep the order, because ties are ordered by id.
     */
    private function ensureOrderIsKept(JobApplicationEvent $event, CarbonInterface $occurredAt): void
    {
        $timeline = $event->jobApplication->events()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $isThisEvent = fn (JobApplicationEvent $other): bool => $other->is($event);

        $previous = $timeline->takeUntil($isThisEvent)->last();
        $next = $timeline->skipUntil($isThisEvent)->skip(1)->first();

        if ($previous !== null && $occurredAt->lessThan($previous->occurred_at)) {
            throw ValidationException::withMessages([
                'occurred_at' => 'An event cannot be dated before the event it follows.',
            ]);
        }

        if ($next !== null && $occurredAt->greaterThan($next->occurred_at)) {
            throw ValidationException::withMessages([
                'occurred_at' => 'An event cannot be dated after the event that follows it.',
            ]);
        }
    }
}

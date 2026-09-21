<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Application events record what already happened, so their date cannot lie
 * in the future (docs/design.md §4.6).
 */
final class EventTime
{
    /**
     * Slack for a browser clock that runs slightly ahead of the server's.
     */
    public const FUTURE_TOLERANCE_MINUTES = 5;

    /**
     * @param  string  $field  the form field the error is shown under
     */
    public static function ensureNotInFuture(CarbonInterface $occurredAt, string $field = 'occurred_at'): void
    {
        $latestAllowed = CarbonImmutable::now()->addMinutes(self::FUTURE_TOLERANCE_MINUTES);

        if ($occurredAt->greaterThan($latestAllowed)) {
            throw ValidationException::withMessages([
                $field => 'The date cannot be in the future.',
            ]);
        }
    }
}

<?php

namespace App\Http\Requests\Concerns;

use Carbon\CarbonInterface;

/**
 * For requests with an optional occurred_at: the browser sends an ISO 8601
 * UTC timestamp, and an empty value means "now", which the Actions default to.
 */
trait ParsesOccurredAt
{
    public function occurredAt(): ?CarbonInterface
    {
        return $this->date('occurred_at')?->utc();
    }
}

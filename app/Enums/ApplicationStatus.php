<?php

namespace App\Enums;

enum ApplicationStatus: string implements HasLabel
{
    case Saved = 'saved';
    case Applied = 'applied';
    case Interviewing = 'interviewing';
    case Offer = 'offer';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /**
     * Closed applications need no further action (docs/design.md §4.5).
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::Accepted, self::Rejected, self::Withdrawn => true,
            default => false,
        };
    }

    /**
     * A new application starts as saved or applied (docs/design.md §5.1).
     */
    public function canBeInitial(): bool
    {
        return $this === self::Saved || $this === self::Applied;
    }

    public function label(): string
    {
        return match ($this) {
            self::Saved => 'Saved',
            self::Applied => 'Applied',
            self::Interviewing => 'Interviewing',
            self::Offer => 'Offer',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Withdrawn => 'Withdrawn',
        };
    }
}

<?php

namespace App\Enums;

/**
 * An enum whose cases have a human-readable label for the UI.
 */
interface HasLabel
{
    public function label(): string;
}

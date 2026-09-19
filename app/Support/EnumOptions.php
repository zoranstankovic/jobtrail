<?php

namespace App\Support;

use App\Enums\HasLabel;
use BackedEnum;

/**
 * Turns a labelled enum into the value/label pairs a select box needs, so the
 * frontend never hardcodes enum values (docs/design.md §4).
 */
final class EnumOptions
{
    /**
     * @param  class-string<BackedEnum&HasLabel>  $enum
     * @return list<array{value: int|string, label: string}>
     */
    public static function for(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[] = ['value' => $case->value, 'label' => $case->label()];
        }

        return $options;
    }
}

<?php

namespace App\Support;

use BackedEnum;

/**
 * Builds a CHECK constraint from a PHP backed enum, so the enum stays the
 * single source of truth for a column's allowed values (docs/design.md §4).
 */
final class EnumCheck
{
    /**
     * @param  class-string<BackedEnum>  $enum
     */
    public static function sql(string $table, string $column, string $enum): string
    {
        $values = implode(', ', array_map(
            fn (BackedEnum $case): string => "'{$case->value}'",
            $enum::cases(),
        ));

        return "alter table {$table} add constraint {$table}_{$column}_check check ({$column} in ({$values}))";
    }
}

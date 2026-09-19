<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Fails when another row already holds the value, compared case-insensitively
 * and ignoring surrounding whitespace, so "ACME" clashes with "Acme". It asks
 * the same question as the lower(column) unique indexes, so the user gets a
 * message instead of a database error (docs/design.md §5 "Error handling").
 */
final class UniqueCaseInsensitive implements ValidationRule
{
    /**
     * The table and column are interpolated into SQL, so they must be written
     * in our code; literal-string lets Larastan enforce that.
     *
     * @param  literal-string  $table
     * @param  literal-string  $column
     */
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $taken = DB::table($this->table)
            ->whereRaw("lower({$this->column}) = lower(?)", [trim($value)])
            ->when($this->ignoreId !== null, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($taken) {
            $fail('The :attribute has already been taken.');
        }
    }
}

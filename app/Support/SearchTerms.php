<?php

namespace App\Support;

/**
 * Prepares search-box input for websearch_to_tsquery (docs/design.md §4.3).
 * The index stores "Laravel/Vue" as the words "laravel" and "vue", so the
 * search terms cannot keep the slash either.
 */
final class SearchTerms
{
    /**
     * A term with slashes becomes a phrase ("Laravel/Vue" -> "Laravel Vue"):
     * it matches the two words next to each other, and "-Laravel/Vue" still
     * excludes the whole term. Inside a quoted phrase, slashes become spaces.
     */
    public static function forWebSearch(string $terms): string
    {
        return preg_replace_callback('/"[^"]*"?|\S+/', function (array $match): string {
            $term = $match[0];

            if (str_starts_with($term, '"') || ! str_contains($term, '/')) {
                return str_replace('/', ' ', $term);
            }

            $exclude = str_starts_with($term, '-') ? '-' : '';

            return $exclude.'"'.str_replace('/', ' ', ltrim($term, '-')).'"';
        }, $terms) ?? $terms;
    }
}

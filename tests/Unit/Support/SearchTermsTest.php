<?php

use App\Support\SearchTerms;

it('rewrites slashes for websearch_to_tsquery', function (string $terms, string $expected): void {
    expect(SearchTerms::forWebSearch($terms))->toBe($expected);
})->with([
    'plain words' => ['laravel -vue', 'laravel -vue'],
    'a slashed term becomes a phrase' => ['Laravel/Vue', '"Laravel Vue"'],
    'an excluded slashed term' => ['-Laravel/Vue', '-"Laravel Vue"'],
    'slashes inside a phrase' => ['"full stack/laravel" php', '"full stack laravel" php'],
    'several slashes' => ['m/w/d', '"m w d"'],
    'next to or' => ['Laravel/Vue or React', '"Laravel Vue" or React'],
]);

<?php

// What the production build must never receive from a developer's checkout
// (docs/deployment.md). A leftover public/hot makes every page load its
// assets from the Vite dev server; the cached package manifest lists
// dev-only service providers; .env files hold secrets.

function dockerignoreEntries(): array
{
    $file = dirname(__DIR__, 3).'/.dockerignore';

    return array_values(array_filter(
        array_map(trim(...), file($file)),
        fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#'),
    ));
}

it('keeps local state out of the image build context', function (string $entry): void {
    expect(dockerignoreEntries())->toContain($entry);
})->with([
    '.env*',
    'public/hot',
    'public/fonts-manifest.dev.json',
    'public/build',
    'bootstrap/cache/*.php',
    'node_modules',
    'vendor',
    'storage/logs',
    'resources/js/actions',
    'resources/js/routes',
    'resources/js/wayfinder',
]);

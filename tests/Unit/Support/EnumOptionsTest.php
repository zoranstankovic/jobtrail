<?php

use App\Enums\WorkMode;
use App\Support\EnumOptions;

it('lists value and label pairs in case order', function (): void {
    expect(EnumOptions::for(WorkMode::class))->toBe([
        ['value' => 'onsite', 'label' => 'On-site'],
        ['value' => 'hybrid', 'label' => 'Hybrid'],
        ['value' => 'remote', 'label' => 'Remote'],
    ]);
});

<?php

use App\Models\Company;
use App\Models\JobPosting;

it('seeds the demo data into an empty database', function (): void {
    $this->artisan('app:seed-demo-if-empty')
        ->expectsOutputToContain('Demo data seeded')
        ->assertSuccessful();

    expect(Company::query()->count())->toBe(12)
        ->and(JobPosting::query()->count())->toBe(40);
});

it('leaves a database with companies untouched', function (): void {
    Company::factory()->create();

    $this->artisan('app:seed-demo-if-empty')
        ->expectsOutputToContain('skipping the demo seed')
        ->assertSuccessful();

    expect(Company::query()->count())->toBe(1)
        ->and(JobPosting::query()->count())->toBe(0);
});

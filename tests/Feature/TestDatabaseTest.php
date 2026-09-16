<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('runs against the PostgreSQL testing database', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(DB::connection()->getDatabaseName())->toBe('jobtrail_testing');
});

it('has the migrations applied to the testing database', function (): void {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('migrations'))->toBeTrue();
});

it('never touches the development database', function (): void {
    expect(DB::connection()->getDatabaseName())->not->toBe('jobtrail');
});

<?php

namespace App\Console\Commands;

use App\Models\Company;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs on every container start (docker/app/entrypoint.sh), so it must never
 * touch existing data (docs/design.md §3.2, §3.3).
 */
#[Signature('app:seed-demo-if-empty')]
#[Description('Seed the demo data, but only when the database has no companies yet')]
class SeedDemoIfEmpty extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Company::query()->exists()) {
            $this->components->info('Companies already exist; skipping the demo seed.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->components->info('Demo data seeded.');

        return self::SUCCESS;
    }
}

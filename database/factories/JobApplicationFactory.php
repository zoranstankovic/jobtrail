<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates the matching creation event too, so a factory-made application
 * satisfies the consistency invariant (docs/design.md §5.8).
 *
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'status' => ApplicationStatus::Saved,
            'applied_at' => null,
            'notes' => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (JobApplication $application): void {
            $application->events()->create([
                'from_status' => null,
                'to_status' => $application->status,
                'occurred_at' => $application->applied_at ?? $application->created_at,
            ]);
        });
    }

    /**
     * An application created as already applied.
     */
    public function applied(?CarbonInterface $at = null): static
    {
        return $this->state(fn (): array => [
            'status' => ApplicationStatus::Applied,
            'applied_at' => $at ?? CarbonImmutable::now(),
        ]);
    }
}

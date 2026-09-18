<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw event rows for schema tests. It does not update the application's
 * status, so use the Actions whenever the invariant matters.
 *
 * @extends Factory<JobApplicationEvent>
 */
class JobApplicationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_application_id' => JobApplication::factory(),
            'from_status' => ApplicationStatus::Saved,
            'to_status' => ApplicationStatus::Applied,
            'occurred_at' => now(),
            'note' => null,
        ];
    }
}

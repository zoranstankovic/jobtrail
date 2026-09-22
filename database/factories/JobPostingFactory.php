<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * salary_currency is left out on purpose so the database default applies.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'url' => fake()->unique()->url(),
            'source' => fake()->randomElement(['linkedin', 'stepstone', 'xing', 'indeed', 'direct']),
            'location' => fake()->randomElement(['Berlin', 'München', 'Hamburg', 'Köln', 'Remote']),
            'work_mode' => fake()->randomElement(WorkMode::cases()),
            'employment_type' => EmploymentType::FullTime,
            'seniority' => fake()->randomElement(Seniority::cases()),
            'description' => fake()->paragraphs(3, true),
        ];
    }
}

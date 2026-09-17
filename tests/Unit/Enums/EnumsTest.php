<?php

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;

it('defines the values from the design document', function (string $enum, array $values): void {
    /** @var class-string<BackedEnum> $enum */
    $actual = array_map(fn (BackedEnum $case): string|int => $case->value, $enum::cases());

    expect($actual)->toBe($values);
})->with([
    'ApplicationStatus' => [ApplicationStatus::class, ['saved', 'applied', 'interviewing', 'offer', 'accepted', 'rejected', 'withdrawn']],
    'WorkMode' => [WorkMode::class, ['onsite', 'hybrid', 'remote']],
    'EmploymentType' => [EmploymentType::class, ['full_time', 'part_time', 'contract', 'internship']],
    'Seniority' => [Seniority::class, ['intern', 'junior', 'mid', 'senior', 'lead']],
    'SalaryPeriod' => [SalaryPeriod::class, ['yearly', 'monthly', 'hourly']],
]);

it('treats accepted, rejected and withdrawn as closed', function (): void {
    $closed = array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $status): bool => $status->isClosed());

    expect(array_values($closed))->toBe([
        ApplicationStatus::Accepted,
        ApplicationStatus::Rejected,
        ApplicationStatus::Withdrawn,
    ]);
});

it('allows only saved and applied as the initial status', function (): void {
    $initial = array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $status): bool => $status->canBeInitial());

    expect(array_values($initial))->toBe([
        ApplicationStatus::Saved,
        ApplicationStatus::Applied,
    ]);
});

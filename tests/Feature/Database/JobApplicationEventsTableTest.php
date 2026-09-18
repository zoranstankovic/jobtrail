<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('gives a factory-made application its creation event', function (): void {
    $application = JobApplication::factory()->create();

    $event = $application->events()->sole();

    expect($event->from_status)->toBeNull()
        ->and($event->to_status)->toBe(ApplicationStatus::Saved);

    expectApplicationToBeConsistent($application);
});

it('dates the creation event of an applied application at applied_at', function (): void {
    $application = JobApplication::factory()->applied(CarbonImmutable::parse('2026-08-01 09:00:00'))->create();

    expect($application->events()->sole()->occurred_at->toDateTimeString())->toBe('2026-08-01 09:00:00');

    expectApplicationToBeConsistent($application);
});

it('records when the event row was written, and has no updated_at', function (): void {
    $application = JobApplication::factory()->create();

    expect($application->events()->sole()->created_at)->not->toBeNull()
        ->and(Schema::hasColumn('job_application_events', 'updated_at'))->toBeFalse();
});

it('picks the event with the greatest occurred_at as the latest', function (): void {
    $application = JobApplication::factory()->create();
    $later = JobApplicationEvent::factory()->for($application)->create(['occurred_at' => '2030-01-02 10:00:00']);
    JobApplicationEvent::factory()->for($application)->create(['occurred_at' => '2030-01-01 10:00:00']);

    expect($application->latestEvent?->is($later))->toBeTrue();
});

it('breaks an occurred_at tie by the greatest id', function (): void {
    $application = JobApplication::factory()->create();
    JobApplicationEvent::factory()->for($application)->create(['occurred_at' => '2030-01-01 10:00:00']);
    $second = JobApplicationEvent::factory()->for($application)->create(['occurred_at' => '2030-01-01 10:00:00']);

    expect($application->latestEvent?->is($second))->toBeTrue();
});

it('deletes the events when the application is deleted', function (): void {
    $application = JobApplication::factory()->create();

    $application->delete();

    expect(JobApplicationEvent::query()->count())->toBe(0);
});

it('rejects an unknown status', function (string $column): void {
    $application = JobApplication::factory()->create();

    expect(fn () => DB::table('job_application_events')->insert([
        'job_application_id' => $application->id,
        'from_status' => 'saved',
        'to_status' => 'applied',
        'occurred_at' => now(),
        $column => 'ghosted',
    ]))->toThrow(QueryException::class, "job_application_events_{$column}_check");
})->with(['from_status', 'to_status']);

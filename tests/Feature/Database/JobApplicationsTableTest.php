<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('stores an application for a posting', function (): void {
    $posting = JobPosting::factory()->create();

    $application = JobApplication::factory()->for($posting)->create([
        'status' => ApplicationStatus::Interviewing,
    ]);

    expect($application->fresh()?->status)->toBe(ApplicationStatus::Interviewing)
        ->and($posting->application?->is($application))->toBeTrue()
        ->and($application->jobPosting->is($posting))->toBeTrue();
});

it('refuses to delete a posting that has an application', function (): void {
    $application = JobApplication::factory()->create();

    expect(fn () => $application->jobPosting->delete())
        ->toThrow(QueryException::class, 'job_applications_job_posting_id_foreign');
});

it('rejects a second application for the same posting', function (): void {
    $posting = JobPosting::factory()->create();
    JobApplication::factory()->for($posting)->create();

    expect(fn () => JobApplication::factory()->for($posting)->create())
        ->toThrow(UniqueConstraintViolationException::class, 'job_applications_job_posting_id_unique');
});

it('rejects an unknown status', function (): void {
    $posting = JobPosting::factory()->create();

    expect(fn () => DB::table('job_applications')->insert([
        'job_posting_id' => $posting->id,
        'status' => 'ghosted',
    ]))->toThrow(QueryException::class, 'job_applications_status_check');
});

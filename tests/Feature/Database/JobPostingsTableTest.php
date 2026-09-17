<?php

use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('stores a posting for a company', function (): void {
    $company = Company::factory()->create();

    $posting = JobPosting::factory()->for($company)->create([
        'title' => 'Senior PHP Developer',
        'work_mode' => WorkMode::Hybrid,
    ]);

    $fresh = $posting->fresh();

    expect($fresh?->company->is($company))->toBeTrue()
        ->and($fresh?->work_mode)->toBe(WorkMode::Hybrid)
        ->and($company->jobPostings()->count())->toBe(1);
});

it('stores the source trimmed and lowercase', function (): void {
    $posting = JobPosting::factory()->create(['source' => '  LinkedIn ']);

    expect($posting->fresh()?->source)->toBe('linkedin');
});

it('defaults the salary currency to EUR', function (): void {
    $posting = JobPosting::factory()->create();

    expect($posting->fresh()?->salary_currency)->toBe('EUR');
});

it('allows any number of postings without a URL', function (): void {
    JobPosting::factory()->count(2)->create(['url' => null]);

    expect(JobPosting::query()->whereNull('url')->count())->toBe(2);
});

it('rejects two postings with the same URL', function (): void {
    JobPosting::factory()->create(['url' => 'https://jobs.example/1']);

    expect(fn () => JobPosting::factory()->create(['url' => 'https://jobs.example/1']))
        ->toThrow(UniqueConstraintViolationException::class, 'job_postings_url_unique');
});

it('allows manual postings and the same external id from different connectors', function (): void {
    JobPosting::factory()->count(2)->create(['connector' => null, 'external_id' => null]);
    JobPosting::factory()->create(['connector' => 'arbeitnow', 'external_id' => '42']);
    JobPosting::factory()->create(['connector' => 'adzuna', 'external_id' => '42']);

    expect(JobPosting::query()->count())->toBe(4);
});

it('rejects the same external id twice from one connector', function (): void {
    JobPosting::factory()->create(['connector' => 'arbeitnow', 'external_id' => '42']);

    expect(fn () => JobPosting::factory()->create(['connector' => 'arbeitnow', 'external_id' => '42']))
        ->toThrow(UniqueConstraintViolationException::class, 'job_postings_connector_external_id_unique');
});

it('allows a salary with only one bound', function (): void {
    JobPosting::factory()->create(['salary_min' => 60000, 'salary_max' => null]);
    JobPosting::factory()->create(['salary_min' => null, 'salary_max' => 80000]);

    expect(JobPosting::query()->count())->toBe(2);
});

it('rejects a minimum salary above the maximum', function (): void {
    expect(fn () => JobPosting::factory()->create(['salary_min' => 90000, 'salary_max' => 60000]))
        ->toThrow(QueryException::class, 'job_postings_salary_check');
});

it('rejects values outside the enum', function (string $column): void {
    $company = Company::factory()->create();

    // Bypasses the model on purpose: the enum cast would reject 'bogus' in PHP
    // before the database ever saw it.
    expect(fn () => DB::table('job_postings')->insert([
        'company_id' => $company->id,
        'title' => 'Backend Engineer',
        'source' => 'linkedin',
        $column => 'bogus',
    ]))->toThrow(QueryException::class, "job_postings_{$column}_check");
})->with(['work_mode', 'employment_type', 'seniority', 'salary_period']);

it('refuses to delete a company that still has postings', function (): void {
    $posting = JobPosting::factory()->create();

    expect(fn () => $posting->company->delete())
        ->toThrow(QueryException::class, 'job_postings_company_id_foreign');
});

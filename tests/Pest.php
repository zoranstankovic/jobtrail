<?php

use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Assert the consistency invariant (docs/design.md §5.8): status equals the
 * latest event's to_status, and applied_at equals the occurred_at of the
 * earliest "applied" event. Reads raw rows so it never reuses the code under
 * test.
 */
function expectApplicationToBeConsistent(JobApplication $application): void
{
    $events = DB::table('job_application_events')
        ->where('job_application_id', $application->id)
        ->orderBy('occurred_at')
        ->orderBy('id')
        ->get();

    $row = DB::table('job_applications')->where('id', $application->id)->first();

    expect($row)->not->toBeNull()
        ->and($events)->not->toBeEmpty()
        ->and($row->status)->toBe($events->last()->to_status)
        ->and($row->applied_at)->toBe($events->firstWhere('to_status', 'applied')?->occurred_at);
}

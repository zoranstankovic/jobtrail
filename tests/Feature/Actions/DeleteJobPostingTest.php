<?php

use App\Actions\DeleteJobPosting;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Skill;
use Illuminate\Validation\ValidationException;

it('deletes a posting without an application and keeps its skills', function (): void {
    $posting = JobPosting::factory()->create();
    $skill = Skill::factory()->create();
    $posting->skills()->attach($skill);

    app(DeleteJobPosting::class)->handle($posting);

    expect($posting->fresh())->toBeNull()
        ->and($skill->fresh())->not->toBeNull()
        ->and($skill->jobPostings()->count())->toBe(0);
});

it('refuses to delete a posting that has an application', function (): void {
    $application = JobApplication::factory()->create();
    $posting = $application->jobPosting;

    expect(fn () => app(DeleteJobPosting::class)->handle($posting))
        ->toThrow(ValidationException::class, 'Delete the application first');

    expect($posting->fresh())->not->toBeNull()
        ->and($application->fresh())->not->toBeNull();
});

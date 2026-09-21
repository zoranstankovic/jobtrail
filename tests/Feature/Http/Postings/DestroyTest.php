<?php

use App\Actions\CreateJobApplication;
use App\Models\JobPosting;
use App\Models\Skill;

it('deletes a posting without an application, with its skill links', function (): void {
    $posting = JobPosting::factory()->create();
    $skill = Skill::factory()->create();
    $posting->skills()->attach($skill->id);

    $this->delete("/postings/{$posting->id}")
        ->assertRedirect('/postings')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Job posting deleted.']);

    expect($posting->fresh())->toBeNull()
        ->and($skill->fresh())->not->toBeNull()
        ->and($skill->jobPostings()->count())->toBe(0);
});

it('refuses to delete a posting that has an application', function (): void {
    $posting = JobPosting::factory()->create();
    app(CreateJobApplication::class)->handle($posting);

    $this->from("/postings/{$posting->id}")
        ->delete("/postings/{$posting->id}")
        ->assertRedirect("/postings/{$posting->id}")
        ->assertSessionHasErrors(['job_posting' => 'This posting has an application. Delete the application first.']);

    expect($posting->fresh())->not->toBeNull();
});

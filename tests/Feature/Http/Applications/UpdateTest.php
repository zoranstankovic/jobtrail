<?php

it('saves the application notes', function (): void {
    $application = createApplication();

    $this->patch("/applications/{$application->id}", ['notes' => "Recruiter: Jana\nFollow up Friday"])
        ->assertRedirect("/postings/{$application->job_posting_id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Notes saved.']);

    expect($application->fresh()?->notes)->toBe("Recruiter: Jana\nFollow up Friday");

    expectApplicationToBeConsistent($application);
});

it('clears the notes when the field is emptied', function (): void {
    $application = createApplication();
    $application->update(['notes' => 'Old']);

    $this->patch("/applications/{$application->id}", ['notes' => '']);

    expect($application->fresh()?->notes)->toBeNull();
});

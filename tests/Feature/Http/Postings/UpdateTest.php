<?php

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the edit form with the posting and the suggestions', function (): void {
    $posting = JobPosting::factory()->create(['title' => 'PHP Developer']);

    $this->get("/postings/{$posting->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('postings/Edit')
            ->where('posting.id', $posting->id)
            ->where('posting.title', 'PHP Developer')
            ->where('posting.company.name', $posting->company->name)
            ->where('companies', [$posting->company->name])
            ->has('sources')
            ->has('skills'));
});

it('updates a posting and redirects to it', function (): void {
    $posting = JobPosting::factory()->create();
    $posting->skills()->attach(Skill::factory()->create(['name' => 'PHP'])->id);

    $this->put("/postings/{$posting->id}", postingInput([
        'company' => 'Nordlicht Software GmbH',
        'title' => 'Staff Engineer',
        'skills' => ['Go'],
    ]))
        ->assertRedirect("/postings/{$posting->id}")
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Job posting updated.']);

    $fresh = $posting->fresh();

    expect($fresh?->title)->toBe('Staff Engineer')
        ->and($fresh?->company->name)->toBe('Nordlicht Software GmbH')
        ->and($fresh?->skills->pluck('name')->all())->toBe(['Go']);
});

it('allows keeping the own URL', function (): void {
    $posting = JobPosting::factory()->create(['url' => 'https://jobs.example/laravel']);

    $this->put("/postings/{$posting->id}", postingInput(['url' => 'https://jobs.example/LARAVEL']))
        ->assertSessionHasNoErrors();
});

it('rejects the URL of another posting', function (): void {
    JobPosting::factory()->create(['url' => 'https://jobs.example/taken']);
    $posting = JobPosting::factory()->create(['title' => 'Unchanged']);

    $this->put("/postings/{$posting->id}", postingInput(['url' => 'https://jobs.example/taken', 'title' => 'Changed']))
        ->assertSessionHasErrors(['url' => 'The url has already been taken.']);

    expect($posting->fresh()?->title)->toBe('Unchanged');
});

it('rejects invalid input', function (): void {
    $posting = JobPosting::factory()->create();

    $this->put("/postings/{$posting->id}", postingInput(['title' => '']))
        ->assertSessionHasErrors('title');
});

it('keeps the company when the name is unchanged apart from case', function (): void {
    $company = Company::factory()->create(['name' => 'Acme GmbH']);
    $posting = JobPosting::factory()->for($company)->create();

    $this->put("/postings/{$posting->id}", postingInput(['company' => 'ACME GMBH']));

    expect($posting->fresh()?->company_id)->toBe($company->id)
        ->and(Company::query()->count())->toBe(1);
});

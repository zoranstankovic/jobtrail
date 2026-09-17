<?php

use App\Models\JobPosting;
use Illuminate\Support\Facades\Schema;

function searchPostings(string $terms): array
{
    return JobPosting::query()
        ->whereRaw('search_vector @@ websearch_to_tsquery(?, ?)', [JobPosting::SEARCH_CONFIG, $terms])
        ->orderByRaw('ts_rank(search_vector, websearch_to_tsquery(?, ?)) desc', [JobPosting::SEARCH_CONFIG, $terms])
        ->orderBy('id')
        ->pluck('title')
        ->all();
}

it('finds postings by words in the title or the description', function (): void {
    JobPosting::factory()->create(['title' => 'Senior PHP Entwickler', 'description' => 'Wir nutzen Laravel.']);
    JobPosting::factory()->create(['title' => 'Frontend Developer', 'description' => 'Vue and TypeScript.']);

    expect(searchPostings('entwickler'))->toBe(['Senior PHP Entwickler'])
        ->and(searchPostings('typescript'))->toBe(['Frontend Developer']);
});

it('indexes a posting without a description by its title', function (): void {
    JobPosting::factory()->create(['title' => 'DevOps Engineer', 'description' => null]);

    expect(searchPostings('devops'))->toBe(['DevOps Engineer']);
});

it('ranks a title match above a description match', function (): void {
    JobPosting::factory()->create(['title' => 'Backend Engineer', 'description' => 'We use Laravel every day.']);
    JobPosting::factory()->create(['title' => 'Laravel Developer', 'description' => 'Backend work.']);

    expect(searchPostings('laravel'))->toBe(['Laravel Developer', 'Backend Engineer']);
});

it('keeps the search vector up to date when a posting changes', function (): void {
    $posting = JobPosting::factory()->create(['title' => 'Backend Engineer', 'description' => 'PHP']);

    $posting->update(['description' => 'Kubernetes']);

    expect(searchPostings('kubernetes'))->toBe(['Backend Engineer']);
});

it('backs the search vector with a GIN index', function (): void {
    $index = collect(Schema::getIndexes('job_postings'))
        ->firstWhere('name', 'job_postings_search_vector_index');

    expect($index)->not->toBeNull()
        ->and($index['type'])->toBe('gin');
});

it('keeps the search vector out of serialized postings', function (): void {
    $posting = JobPosting::factory()->create();

    expect($posting->fresh()?->toArray())->not->toHaveKey('search_vector');
});

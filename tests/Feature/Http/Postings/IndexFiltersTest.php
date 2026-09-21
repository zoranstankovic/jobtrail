<?php

use App\Models\JobPosting;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the active filters with the page', function (): void {
    $this->get('/postings')->assertInertia(fn (Assert $page) => $page
        ->where('filters.search', null));
});

it('searches titles and descriptions with full-text search', function (): void {
    JobPosting::factory()->create(['title' => 'Senior Laravel Developer', 'description' => 'Backend work.']);
    JobPosting::factory()->create(['title' => 'Backend Engineer', 'description' => 'We use Laravel and Vue.']);
    JobPosting::factory()->create(['title' => 'Go Engineer', 'description' => 'Microservices.']);

    $this->get('/postings?search=laravel')->assertInertia(fn (Assert $page) => $page
        ->where('filters.search', 'laravel')
        ->where('postings.data', fn (Collection $postings) => $postings->pluck('title')->all() === [
            'Backend Engineer',
            'Senior Laravel Developer',
        ]));
});

it('understands web search syntax', function (): void {
    JobPosting::factory()->create(['title' => 'Senior Laravel Developer', 'description' => 'Backend work.']);
    JobPosting::factory()->create(['title' => 'Backend Engineer', 'description' => 'We use Laravel and Vue.']);

    $this->get('/postings?search='.urlencode('laravel -vue'))->assertInertia(fn (Assert $page) => $page
        ->where('postings.data', fn (Collection $postings) => $postings->pluck('title')->all() === [
            'Senior Laravel Developer',
        ]));
});

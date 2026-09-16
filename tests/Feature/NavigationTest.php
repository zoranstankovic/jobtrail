<?php

use Inertia\Testing\AssertableInertia as Assert;

it('redirects the root path to the postings screen', function (): void {
    $this->get('/')->assertRedirect('/postings');
});

it('renders the job postings screen', function (): void {
    $this->get('/postings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('postings/Index'));
});

it('renders the applications screen', function (): void {
    $this->get('/applications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('applications/Index'));
});

it('renders the companies screen', function (): void {
    $this->get('/companies')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('companies/Index'));
});

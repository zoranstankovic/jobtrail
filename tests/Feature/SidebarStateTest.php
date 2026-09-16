<?php

use Inertia\Testing\AssertableInertia as Assert;

it('opens the sidebar when there is no sidebar_state cookie', function (): void {
    $this->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('sidebarOpen', true));
});

// The sidebar component writes this cookie from JavaScript, so it arrives
// unencrypted and must be excluded from cookie encryption.
it('keeps the sidebar collapsed when the sidebar_state cookie says so', function (): void {
    $this->withUnencryptedCookie('sidebar_state', 'false')
        ->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('sidebarOpen', false));
});

it('opens the sidebar when the sidebar_state cookie says so', function (): void {
    $this->withUnencryptedCookie('sidebar_state', 'true')
        ->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('sidebarOpen', true));
});

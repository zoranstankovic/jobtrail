<?php

use Inertia\Testing\AssertableInertia as Assert;

it('follows the system theme when there is no appearance cookie', function (): void {
    $this->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('appearance', 'system'))
        ->assertDontSee('class="dark"', false)
        ->assertSee("matchMedia('(prefers-color-scheme: dark)')", false);
});

// The theme toggle writes this cookie from JavaScript, so it arrives
// unencrypted and must be excluded from cookie encryption.
it('renders the dark theme on the server when the cookie says dark', function (): void {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('appearance', 'dark'))
        ->assertSee('class="dark"', false)
        ->assertDontSee('prefers-color-scheme', false)
        ->assertSee('html.dark { background-color: hsl(0 0% 3.9%); color-scheme: dark; }', false);
});

it('renders the light theme when the cookie says light', function (): void {
    $this->withUnencryptedCookie('appearance', 'light')
        ->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('appearance', 'light'))
        ->assertDontSee('class="dark"', false)
        ->assertDontSee('prefers-color-scheme', false);
});

it('treats an unknown appearance cookie as system', function (): void {
    $this->withUnencryptedCookie('appearance', 'purple')
        ->get('/postings')
        ->assertInertia(fn (Assert $page) => $page->where('appearance', 'system'));
});

it('sets the colour scheme of native controls before the stylesheet loads', function (): void {
    // "system" decides in the browser, so both rules must be in the page.
    $this->get('/postings')
        ->assertSee('html { background-color: hsl(0 0% 100%); color-scheme: light; }', false)
        ->assertSee('html.dark { background-color: hsl(0 0% 3.9%); color-scheme: dark; }', false);
});

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
        ->assertDontSee('prefers-color-scheme', false);
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

it('sets the colour scheme and background of app.css before the stylesheet loads', function (): void {
    // The inline style stands in for app.css until it has loaded, so it must
    // carry the values app.css sets on :root and .dark.
    $css = (string) file_get_contents(resource_path('css/app.css'));
    $inline = function (string $selector) use ($css): string {
        preg_match('/^'.preg_quote($selector, '/').' \{\n(.*?)^\}/ms', $css, $rule);
        preg_match('/^\s+color-scheme: (\w+);$/m', $rule[1] ?? '', $scheme);
        preg_match('/^\s+--background: (.+);$/m', $rule[1] ?? '', $background);

        expect($scheme)->toHaveKey(1)->and($background)->toHaveKey(1);

        return "{ background-color: {$background[1]}; color-scheme: {$scheme[1]}; }";
    };

    // "system" decides in the browser, so both rules must be in the page.
    $this->get('/postings')
        ->assertSee('html '.$inline(':root'), false)
        ->assertSee('html.dark '.$inline('.dark'), false);
});

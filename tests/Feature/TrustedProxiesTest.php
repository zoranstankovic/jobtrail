<?php

use App\Models\Company;

// In production, tailscale serve ends HTTPS on the server and forwards plain
// HTTP to nginx, which reaches PHP from a Docker network address. Laravel may
// believe the forwarded scheme from private addresses, and only from them.
// A redirect after a form (to_route) carries an absolute URL; host and port
// come from APP_URL in tests, so only the scheme is compared.

it('trusts the forwarded scheme from a private-network proxy', function (string $proxy): void {
    $company = Company::factory()->create();

    $location = $this->withServerVariables(['REMOTE_ADDR' => $proxy])
        ->withHeader('X-Forwarded-Proto', 'https')
        ->delete("/companies/{$company->id}")
        ->assertRedirect()
        ->headers->get('Location');

    expect($location)->toStartWith('https://')->toEndWith('/companies');
})->with([
    'Docker network gateway' => '172.18.0.1',
    'loopback' => '127.0.0.1',
    'Tailscale address' => '100.101.102.103',
]);

it('ignores the forwarded scheme from a public address', function (): void {
    $company = Company::factory()->create();

    // Not a documentation range: Symfony counts 192.0.2.0/24, 198.51.100.0/24
    // and 203.0.113.0/24 as private subnets.
    $location = $this->withServerVariables(['REMOTE_ADDR' => '93.184.215.14'])
        ->withHeader('X-Forwarded-Proto', 'https')
        ->delete("/companies/{$company->id}")
        ->assertRedirect()
        ->headers->get('Location');

    expect($location)->toStartWith('http://')->toEndWith('/companies');
});

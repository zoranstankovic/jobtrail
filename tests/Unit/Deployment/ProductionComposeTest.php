<?php

use Symfony\Component\Yaml\Yaml;

// Guards for compose.prod.yaml (docs/deployment.md). Docker's published ports
// bypass the host firewall, so only nginx may be published, and only on the
// loopback interface that tailscale serve forwards to.

function productionCompose(): array
{
    return Yaml::parseFile(dirname(__DIR__, 3).'/compose.prod.yaml');
}

it('publishes only nginx, and only on the loopback interface', function (): void {
    $published = collect(productionCompose()['services'])
        ->map(fn (array $service): array => $service['ports'] ?? [])
        ->filter()
        ->all();

    expect($published)->toBe(['web' => ['127.0.0.1:8080:80']]);
});

it('reads the database password from a secret, never from the environment', function (): void {
    $db = productionCompose()['services']['db'];

    expect($db['environment'])->not->toHaveKey('POSTGRES_PASSWORD')
        ->and($db['environment']['POSTGRES_PASSWORD_FILE'])->toBe('/run/secrets/db_password')
        ->and($db['secrets'])->toBe(['db_password'])
        ->and(productionCompose()['secrets']['db_password']['file'])->toBe('./secrets/db_password');
});

it('runs the app and web images of one commit, and refuses to start without it', function (): void {
    $services = productionCompose()['services'];

    expect($services['app']['image'])->toBe('ghcr.io/zoranstankovic/jobtrail-app:${TAG:?Set TAG to the commit sha to run}')
        ->and($services['web']['image'])->toBe('ghcr.io/zoranstankovic/jobtrail-web:${TAG:?Set TAG to the commit sha to run}');
});

it('mounts the Laravel env file read-only', function (): void {
    expect(productionCompose()['services']['app']['volumes'])->toBe(['./app.env:/var/www/html/.env:ro']);
});

it('brings every service back after a reboot', function (): void {
    foreach (productionCompose()['services'] as $name => $service) {
        expect($service['restart'] ?? null)->toBe('unless-stopped', "service {$name}");
    }
});

it('never shares a project or volume name with the dev stack', function (): void {
    expect(productionCompose()['name'])->toBe('jobtrail-prod');
});

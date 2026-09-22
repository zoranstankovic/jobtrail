<?php

use App\Actions\ResolveSkills;
use App\Models\Skill;

it('creates skills that do not exist yet', function (): void {
    $skills = app(ResolveSkills::class)->handle(['Laravel', 'Vue']);

    expect($skills->pluck('name')->all())->toBe(['Laravel', 'Vue'])
        ->and(Skill::query()->count())->toBe(2);
});

it('reuses an existing skill regardless of letter case', function (): void {
    $existing = Skill::factory()->create(['name' => 'PostgreSQL']);

    $skills = app(ResolveSkills::class)->handle(['postgresql']);

    expect($skills->sole()->is($existing))->toBeTrue()
        ->and($skills->sole()->name)->toBe('PostgreSQL')
        ->and(Skill::query()->count())->toBe(1);
});

it('trims names, skips blanks and collapses duplicates', function (): void {
    $skills = app(ResolveSkills::class)->handle(['Vue', ' vue ', '', '   ', 'VUE', 'Docker ']);

    expect($skills->pluck('name')->all())->toBe(['Vue', 'Docker'])
        ->and(Skill::query()->count())->toBe(2);
});

it('reuses an existing skill when the name differs only in inner whitespace', function (): void {
    $existing = Skill::factory()->create(['name' => 'Vue JS']);

    $skills = app(ResolveSkills::class)->handle(['Vue  JS']);

    expect($skills->sole()->is($existing))->toBeTrue()
        ->and(Skill::query()->count())->toBe(1);
});

it('collapses inner whitespace and the duplicates it hides', function (): void {
    $skills = app(ResolveSkills::class)->handle(['Vue   JS', 'vue js']);

    expect($skills->pluck('name')->all())->toBe(['Vue JS'])
        ->and(Skill::query()->count())->toBe(1);
});

it('returns nothing for no names', function (): void {
    expect(app(ResolveSkills::class)->handle([]))->toBeEmpty();
});

<?php

use App\Models\Skill;
use Illuminate\Database\UniqueConstraintViolationException;

it('keeps the display casing of a skill name', function (): void {
    $skill = Skill::factory()->create(['name' => 'PostgreSQL']);

    expect($skill->fresh()?->name)->toBe('PostgreSQL');
});

it('rejects a skill name that differs only in letter case', function (): void {
    Skill::factory()->create(['name' => 'PostgreSQL']);

    expect(fn () => Skill::factory()->create(['name' => 'postgresql']))
        ->toThrow(UniqueConstraintViolationException::class, 'skills_name_lower_unique');
});

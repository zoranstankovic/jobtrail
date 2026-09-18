<?php

namespace App\Actions;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;

/**
 * Turns skill names typed into the posting form into Skill records.
 * Matching is case-insensitive, and new names are created on the fly
 * (docs/design.md §4.4).
 */
final class ResolveSkills
{
    /**
     * @param  list<string>  $names
     * @return Collection<int, Skill>
     */
    public function handle(array $names): Collection
    {
        $skills = [];
        $seen = [];

        foreach ($names as $name) {
            $name = trim($name);
            $key = mb_strtolower($name);

            if ($name === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            // Same shape as the skills_name_lower_unique index, so the lookup
            // is served by that index.
            $skills[] = Skill::query()->whereRaw('lower(name) = lower(?)', [$name])->first()
                ?? Skill::query()->create(['name' => $name]);
        }

        return new Collection($skills);
    }
}

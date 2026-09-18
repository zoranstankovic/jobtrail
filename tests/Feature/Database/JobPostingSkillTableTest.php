<?php

use App\Models\JobPosting;
use App\Models\Skill;
use Illuminate\Database\UniqueConstraintViolationException;

it('links skills to postings in both directions', function (): void {
    $posting = JobPosting::factory()->create();
    $skill = Skill::factory()->create(['name' => 'Laravel']);

    $posting->skills()->attach($skill);

    expect($posting->skills()->pluck('name')->all())->toBe(['Laravel'])
        ->and($skill->jobPostings()->pluck('job_postings.id')->all())->toBe([$posting->id]);
});

it('removes the links but keeps the skills when a posting is deleted', function (): void {
    $posting = JobPosting::factory()->create();
    $skill = Skill::factory()->create();
    $posting->skills()->attach($skill);

    $posting->delete();

    expect(Skill::query()->whereKey($skill->id)->exists())->toBeTrue()
        ->and($skill->jobPostings()->count())->toBe(0);
});

it('removes the links but keeps the postings when a skill is deleted', function (): void {
    $posting = JobPosting::factory()->create();
    $skill = Skill::factory()->create();
    $posting->skills()->attach($skill);

    $skill->delete();

    expect(JobPosting::query()->whereKey($posting->id)->exists())->toBeTrue()
        ->and($posting->skills()->count())->toBe(0);
});

it('rejects linking the same skill to a posting twice', function (): void {
    $posting = JobPosting::factory()->create();
    $skill = Skill::factory()->create();
    $posting->skills()->attach($skill);

    expect(fn () => $posting->skills()->attach($skill))
        ->toThrow(UniqueConstraintViolationException::class, 'job_posting_skill_pkey');
});

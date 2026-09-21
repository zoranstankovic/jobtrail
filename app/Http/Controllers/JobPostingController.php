<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobPostingIndexRequest;
use App\Models\JobPosting;
use App\Models\Skill;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingController extends Controller
{
    public function index(JobPostingIndexRequest $request): Response
    {
        $filters = $request->filters();

        $postings = JobPosting::query()
            ->filter($filters)
            ->with(['company', 'skills' => fn ($query) => $query->orderBy('name'), 'application'])
            ->sorted($filters['sort'] ?? 'created')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (JobPosting $posting): array => [
                'id' => $posting->id,
                'title' => $posting->title,
                'company' => ['id' => $posting->company->id, 'name' => $posting->company->name],
                'location' => $posting->location,
                'work_mode' => $posting->work_mode,
                'seniority' => $posting->seniority,
                'skills' => $posting->skills->pluck('name'),
                'source' => $posting->source,
                'status' => $posting->application?->status,
            ]);

        return Inertia::render('postings/Index', [
            'postings' => $postings,
            'filters' => $filters,
            'sources' => JobPosting::query()->distinct()->orderBy('source')->pluck('source'),
            'skills' => Skill::query()->orderByRaw('lower(name)')->pluck('name'),
        ]);
    }

    public function show(JobPosting $posting): Response
    {
        return Inertia::render('postings/Show', [
            'posting' => $this->presentPosting($posting),
        ]);
    }

    /**
     * The posting's own fields plus its company and skill names, as the
     * detail and edit pages need them.
     *
     * @return array<string, mixed>
     */
    private function presentPosting(JobPosting $posting): array
    {
        return [
            ...$posting->only([
                'id',
                'title',
                'url',
                'source',
                'location',
                'work_mode',
                'employment_type',
                'seniority',
                'salary_min',
                'salary_max',
                'salary_currency',
                'salary_period',
                'description',
                'posted_at',
                'created_at',
            ]),
            'company' => ['id' => $posting->company->id, 'name' => $posting->company->name],
            'skills' => $posting->skills()->orderByRaw('lower(name)')->pluck('name'),
        ];
    }
}

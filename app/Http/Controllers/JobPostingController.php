<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobPostingIndexRequest;
use App\Models\JobPosting;
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
            ->latest()
            ->orderByDesc('id')
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
        ]);
    }
}

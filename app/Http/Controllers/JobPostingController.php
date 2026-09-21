<?php

namespace App\Http\Controllers;

use App\Actions\CreateJobPosting;
use App\Actions\DeleteJobPosting;
use App\Actions\UpdateJobPosting;
use App\Http\Requests\JobPostingIndexRequest;
use App\Http\Requests\JobPostingRequest;
use App\Http\Requests\StoreJobPostingRequest;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobPosting;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
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

    public function create(): Response
    {
        return Inertia::render('postings/Create', $this->formOptions());
    }

    public function store(StoreJobPostingRequest $request, CreateJobPosting $createJobPosting): RedirectResponse
    {
        $posting = $createJobPosting->handle(
            $request->string('company')->value(),
            $request->postingAttributes(),
            $request->skillNames(),
            $request->appliedAt(),
        );

        $this->toast('Job posting created.');

        return to_route('postings.show', $posting);
    }

    public function show(JobPosting $posting): Response
    {
        return Inertia::render('postings/Show', [
            'posting' => $this->presentPosting($posting),
            'application' => $posting->application === null
                ? null
                : $this->presentApplication($posting->application),
        ]);
    }

    public function edit(JobPosting $posting): Response
    {
        return Inertia::render('postings/Edit', [
            'posting' => $this->presentPosting($posting),
            ...$this->formOptions(),
        ]);
    }

    public function update(JobPostingRequest $request, JobPosting $posting, UpdateJobPosting $updateJobPosting): RedirectResponse
    {
        $updateJobPosting->handle(
            $posting,
            $request->string('company')->value(),
            $request->postingAttributes(),
            $request->skillNames(),
        );

        $this->toast('Job posting updated.');

        return to_route('postings.show', $posting);
    }

    public function destroy(JobPosting $posting, DeleteJobPosting $deleteJobPosting): RedirectResponse
    {
        $deleteJobPosting->handle($posting);

        $this->toast('Job posting deleted.');

        return to_route('postings.index');
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

    /**
     * The application panel's data: status, notes and the timeline, newest
     * first, with the latest non-creation event marked as deletable
     * (docs/design.md §5.4, §6.3).
     *
     * @return array<string, mixed>
     */
    private function presentApplication(JobApplication $application): array
    {
        $events = $application->events()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        $latestId = $events->first()?->id;

        return [
            'id' => $application->id,
            'status' => $application->status,
            'applied_at' => $application->applied_at,
            'notes' => $application->notes,
            'events' => $events->map(fn (JobApplicationEvent $event): array => [
                'id' => $event->id,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'occurred_at' => $event->occurred_at,
                'note' => $event->note,
                'can_delete' => $event->id === $latestId && $event->from_status !== null,
            ]),
        ];
    }

    /**
     * Suggestions for the posting form's inputs.
     *
     * @return array<string, array<int, string>>
     */
    private function formOptions(): array
    {
        return [
            'companies' => Company::query()->orderByRaw('lower(name)')->pluck('name')->all(),
            'sources' => JobPosting::sourceSuggestions(),
            'skills' => Skill::query()->orderByRaw('lower(name)')->pluck('name')->all(),
        ];
    }
}

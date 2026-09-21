<?php

namespace App\Http\Controllers;

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Http\Requests\JobApplicationIndexRequest;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Requests\UpdateJobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicationController extends Controller
{
    public function store(
        StoreJobApplicationRequest $request,
        JobPosting $posting,
        CreateJobApplication $createJobApplication,
    ): RedirectResponse {
        $status = ApplicationStatus::from($request->string('status')->value());

        $createJobApplication->handle($posting, $status, $request->occurredAt());

        $this->toast($status === ApplicationStatus::Applied ? 'Marked as applied.' : 'Saved for later.');

        return to_route('postings.show', $posting);
    }

    public function update(UpdateJobApplicationRequest $request, JobApplication $application): RedirectResponse
    {
        $application->update(['notes' => $request->validated('notes')]);

        $this->toast('Notes saved.');

        return to_route('postings.show', $application->job_posting_id);
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        // The events go with it: ON DELETE CASCADE (docs/design.md §5.5).
        $application->delete();

        $this->toast('Application deleted.');

        return to_route('postings.show', $application->job_posting_id);
    }

    public function index(JobApplicationIndexRequest $request): Response
    {
        $tab = $request->tab();

        $closed = array_values(array_filter(
            ApplicationStatus::cases(),
            fn (ApplicationStatus $status): bool => $status->isClosed(),
        ));

        // The newest occurred_at of each application's events.
        $lastActivity = JobApplicationEvent::query()
            ->selectRaw('max(occurred_at)')
            ->whereColumn('job_application_events.job_application_id', 'job_applications.id');

        $applications = JobApplication::query()
            ->select('job_applications.*')
            ->selectSub($lastActivity, 'last_activity_at')
            ->withCasts(['last_activity_at' => 'immutable_datetime'])
            ->with('jobPosting.company')
            ->when(
                $tab === 'closed',
                // Closed: the most recent outcome first.
                fn (Builder $query) => $query->whereIn('status', $closed)->orderByDesc('last_activity_at'),
                // Active tabs: the longest-quiet application first, to surface follow-ups.
                fn (Builder $query) => $query->where('status', $tab)->orderBy('last_activity_at'),
            )
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (JobApplication $application): array => [
                'id' => $application->id,
                'status' => $application->status,
                'applied_at' => $application->applied_at,
                'last_activity_at' => $application->getAttribute('last_activity_at'),
                'posting' => [
                    'id' => $application->jobPosting->id,
                    'title' => $application->jobPosting->title,
                ],
                'company' => [
                    'id' => $application->jobPosting->company->id,
                    'name' => $application->jobPosting->company->name,
                ],
            ]);

        return Inertia::render('applications/Index', [
            'applications' => $applications,
            'tab' => $tab,
            'tabs' => $this->tabs($closed),
        ]);
    }

    /**
     * The status tabs with their counts. Closed adds up accepted, rejected
     * and withdrawn (docs/design.md §4.5, §6.4).
     *
     * @param  list<ApplicationStatus>  $closed
     * @return list<array{key: string, label: string, count: int}>
     */
    private function tabs(array $closed): array
    {
        /** @var array<string, int> $counts */
        $counts = JobApplication::query()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $count = fn (ApplicationStatus $status): int => (int) ($counts[$status->value] ?? 0);

        $tabs = [];

        foreach ([ApplicationStatus::Saved, ApplicationStatus::Applied, ApplicationStatus::Interviewing, ApplicationStatus::Offer] as $status) {
            $tabs[] = ['key' => $status->value, 'label' => $status->label(), 'count' => $count($status)];
        }

        $tabs[] = ['key' => 'closed', 'label' => 'Closed', 'count' => array_sum(array_map($count, $closed))];

        return $tabs;
    }
}

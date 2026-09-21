<?php

namespace App\Http\Controllers;

use App\Actions\ChangeApplicationStatus;
use App\Actions\DeleteLatestApplicationEvent;
use App\Actions\UpdateApplicationEvent;
use App\Enums\ApplicationStatus;
use App\Http\Requests\ChangeApplicationStatusRequest;
use App\Http\Requests\UpdateApplicationEventRequest;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use Illuminate\Http\RedirectResponse;

/**
 * The application timeline: new status changes, edits and undo
 * (docs/design.md §5.2–5.4).
 */
class ApplicationEventController extends Controller
{
    public function store(
        ChangeApplicationStatusRequest $request,
        JobApplication $application,
        ChangeApplicationStatus $changeApplicationStatus,
    ): RedirectResponse {
        $changeApplicationStatus->handle(
            $application,
            ApplicationStatus::from($request->string('status')->value()),
            $request->occurredAt(),
            $request->validated('note'),
        );

        $this->toast('Status changed.');

        return to_route('postings.show', $application->job_posting_id);
    }

    public function update(
        UpdateApplicationEventRequest $request,
        JobApplicationEvent $event,
        UpdateApplicationEvent $updateApplicationEvent,
    ): RedirectResponse {
        $updateApplicationEvent->handle($event, $request->occurredAt(), $request->validated('note'));

        $this->toast('Event updated.');

        return to_route('postings.show', $event->jobApplication->job_posting_id);
    }

    public function destroy(
        JobApplicationEvent $event,
        DeleteLatestApplicationEvent $deleteLatestApplicationEvent,
    ): RedirectResponse {
        // Read before the delete: afterwards the event row is gone.
        $postingId = $event->jobApplication->job_posting_id;

        $deleteLatestApplicationEvent->handle($event);

        $this->toast('Status change undone.');

        return to_route('postings.show', $postingId);
    }
}

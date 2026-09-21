<?php

namespace App\Http\Controllers;

use App\Actions\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use App\Http\Requests\ChangeApplicationStatusRequest;
use App\Models\JobApplication;
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
}

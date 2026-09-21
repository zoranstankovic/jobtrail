<?php

namespace App\Http\Controllers;

use App\Actions\CreateJobApplication;
use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Requests\UpdateJobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;

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
}

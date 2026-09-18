<?php

namespace App\Actions;

use App\Models\JobPosting;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a posting, but only when it has no application, so an
 * application's status history is never lost by accident
 * (docs/design.md §5.6). The foreign key's ON DELETE RESTRICT backs this up;
 * the posting's skill links cascade.
 */
final class DeleteJobPosting
{
    public function handle(JobPosting $posting): void
    {
        if ($posting->application()->exists()) {
            throw ValidationException::withMessages([
                'job_posting' => 'This posting has an application. Delete the application first.',
            ]);
        }

        $posting->delete();
    }
}

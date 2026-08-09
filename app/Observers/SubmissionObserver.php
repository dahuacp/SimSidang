<?php

namespace App\Observers;

use App\Models\Submission;
use App\Models\SubmissionStatusLog;
use Illuminate\Support\Facades\Auth;

class SubmissionObserver
{
    public function updated(Submission $submission): void
    {
        if ($submission->wasChanged('status')) {
            SubmissionStatusLog::create([
                'submission_id' => $submission->id,
                'status_lama' => $submission->getOriginal('status'),
                'status_baru' => $submission->status,
                'diubah_oleh' => Auth::id(),
            ]);
        }
    }
}

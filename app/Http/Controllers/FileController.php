<?php

namespace App\Http\Controllers;

use App\Models\RevisionAttachment;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function submission(Submission $submission)
    {
        $this->authorize('view', $submission);

        abort_if(! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->judul_laporan.'.pdf');
    }

    public function attachment(RevisionAttachment $attachment)
    {
        $this->authorize('view', $attachment);

        abort_if(! $attachment->file_path || ! Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path);
    }
}

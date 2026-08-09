<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');

        $submissions = Submission::with(['user', 'schedule'])
            ->when($search, fn ($q, $s) => $q->where('judul_laporan', 'like', "%$s%")->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.submissions.index', compact('submissions', 'search'));
    }

    public function show(Submission $submission)
    {
        $this->authorize('viewAdminMenu', User::class);

        $submission->load(['user', 'schedule.dosens', 'revisionNotes.dosen', 'revisionNotes.attachments', 'statusLogs.diubahOleh']);

        return view('admin.submissions.show', compact('submission'));
    }
}

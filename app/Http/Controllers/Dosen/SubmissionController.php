<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $scheduleIds = $user->schedulesAsDosen()->pluck('schedules.id');

        $schedules = Schedule::with(['submissions.user', 'dosens'])
            ->whereIn('id', $scheduleIds)
            ->where('tanggal_sidang', now()->toDateString())
            ->orderBy('jam_mulai')
            ->get();

        return view('dosen.submissions.index', compact('schedules'));
    }

    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load(['schedule', 'revisionNotes.attachments', 'revisionNotes.dosen', 'user', 'statusLogs.diubahOleh']);

        return view('dosen.submissions.show', compact('submission'));
    }
}

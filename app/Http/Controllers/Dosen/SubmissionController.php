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

        $filter = $request->input('filter', 'semua');

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $schedules = Schedule::with(['submissions.user', 'mahasiswas', 'dosens'])
            ->whereIn('id', $scheduleIds)
            ->when($startDate || $endDate, function ($q) use ($startDate, $endDate) {
                $dates = array_filter([$startDate, $endDate]);
                if (count($dates) === 1) {
                    $q->whereDate('tanggal_sidang', $dates[0]);
                } else {
                    $q->whereBetween('tanggal_sidang', $dates);
                }
            })
            ->when(! $startDate && ! $endDate && $filter === 'hari_ini', fn ($q) => $q->whereDate('tanggal_sidang', now()->toDateString()))
            ->orderBy('tanggal_sidang')
            ->orderBy('jam_mulai')
            ->get();

        return view('dosen.submissions.index', compact('schedules', 'filter', 'startDate', 'endDate'));
    }

    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load(['schedule', 'revisionNotes.attachments', 'revisionNotes.dosen', 'user', 'statusLogs.diubahOleh']);

        return view('dosen.submissions.show', compact('submission'));
    }
}

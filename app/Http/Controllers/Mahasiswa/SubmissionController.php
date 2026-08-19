<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $submissions = Submission::with(['schedule', 'revisionNotes'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('mahasiswa.submissions.index', compact('submissions'));
    }

    public function create(Request $request)
    {
        $schedules = $request->user()->schedulesAsPlot()->orderBy('tanggal_sidang')->get();

        return view('mahasiswa.submissions.create', compact('schedules'));
    }

    public function store(StoreSubmissionRequest $request)
    {
        $this->authorize('create', Submission::class);

        $file = $request->file('file');
        $path = $file->store('submissions', 'local');

        Submission::create([
            'user_id' => $request->user()->id,
            'schedule_id' => $request->schedule_id,
            'judul_laporan' => $request->judul_laporan,
            'file_path' => $path,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('mahasiswa.submissions.index')
            ->with('success', 'Laporan berhasil diunggah.');
    }

    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load(['schedule', 'revisionNotes.attachments', 'revisionNotes.dosen', 'statusLogs.diubahOleh']);

        return view('mahasiswa.submissions.show', compact('submission'));
    }
}

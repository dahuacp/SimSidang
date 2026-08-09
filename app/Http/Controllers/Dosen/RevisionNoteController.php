<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevisionNoteRequest;
use App\Http\Requests\UpdateRevisionNoteRequest;
use App\Models\RevisionNote;
use App\Models\Submission;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class RevisionNoteController extends Controller
{
    public function create(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load('revisionNotes.dosen');

        return view('dosen.revision-notes.create', compact('submission'));
    }

    public function store(StoreRevisionNoteRequest $request, Submission $submission, NotificationService $notificationService)
    {
        $this->authorize('view', $submission);
        $this->authorize('create', RevisionNote::class);

        RevisionNote::create([
            'submission_id' => $submission->id,
            'dosen_id' => $request->user()->id,
            'catatan_revisi' => $request->catatan_revisi,
            'status_poin' => 'open',
        ]);

        $submission->update(['status' => 'revisi']);

        $notificationService->send(
            $submission->user_id,
            'revision.note.created',
            ['submission_id' => $submission->id, 'dosen' => $request->user()->name],
            '/mahasiswa/submissions/'.$submission->id
        );

        return redirect()
            ->route('dosen.submissions.show', $submission)
            ->with('success', 'Catatan revisi berhasil ditambahkan.');
    }

    public function resolve(UpdateRevisionNoteRequest $request, RevisionNote $revisionNote, NotificationService $notificationService)
    {
        $this->authorize('resolve', $revisionNote);

        $wasResolved = $request->status_poin === 'resolved';

        $revisionNote->update(['status_poin' => $request->status_poin]);

        $submission = $revisionNote->submission;
        if ($submission->revisionNotes()->where('status_poin', 'open')->doesntExist()) {
            $submission->update(['status' => 'selesai']);
        }

        if ($wasResolved) {
            $notificationService->send(
                $submission->user_id,
                'revision.note.resolved',
                ['submission_id' => $submission->id, 'poin_id' => $revisionNote->id],
                '/mahasiswa/submissions/'.$submission->id
            );
        }

        return redirect()
            ->route('dosen.submissions.show', $submission)
            ->with('success', 'Status poin revisi diperbarui.');
    }
}

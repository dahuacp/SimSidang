<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevisionAttachmentRequest;
use App\Models\RevisionAttachment;
use App\Models\RevisionNote;
use App\Services\NotificationService;

class RevisionAttachmentController extends Controller
{
    public function store(StoreRevisionAttachmentRequest $request, RevisionNote $revisionNote, NotificationService $notificationService)
    {
        $this->authorize('reply', $revisionNote);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('attachments', 'local');
        }

        RevisionAttachment::create([
            'revision_note_id' => $revisionNote->id,
            'keterangan_mahasiswa' => $request->keterangan_mahasiswa,
            'file_path' => $filePath,
        ]);

        $notificationService->send(
            $revisionNote->dosen_id,
            'revision.attachment.replied',
            ['submission_id' => $revisionNote->submission_id, 'poin_id' => $revisionNote->id],
            '/dosen/submissions/'.$revisionNote->submission_id
        );

        return redirect()
            ->route('mahasiswa.submissions.show', $revisionNote->submission_id)
            ->with('success', 'Bukti perbaikan berhasil dikirim.');
    }
}

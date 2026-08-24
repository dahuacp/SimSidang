<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePembimbingRequest;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');
        $sortBy = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $status = $request->input('status');

        $allowedSorts = ['name', 'judul', 'status', 'created_at'];
        $allowedDir = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array($sortDir, $allowedDir) ? $sortDir : 'desc';

        $submissions = Submission::with(['user', 'schedule'])
            ->when($search, fn ($q, $s) => $q->where('judul_laporan', 'like', "%$s%")->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")))
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy($sortBy, $sortDir)
            ->paginate(15)
            ->withQueryString();

        $statusOptions = [
            'draft' => 'Draft',
            'submitted' => 'Dikumpulkan',
            'revised' => 'Direvisi',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
        ];

        return view('admin.submissions.index', compact('submissions', 'search', 'sortBy', 'sortDir', 'status', 'statusOptions'));
    }

    public function show(Submission $submission)
    {
        $this->authorize('viewAdminMenu', User::class);

        $submission->load(['user.prodi.fakultas', 'user.dosenPembimbingByUrutan', 'schedule.dosens', 'revisionNotes.dosen', 'revisionNotes.attachments', 'statusLogs.diubahOleh', 'assessmentForms.dosen', 'assessmentForms.template']);

        return view('admin.submissions.show', compact('submission'));
    }

    public function storePembimbing(StorePembimbingRequest $request, Submission $submission)
    {
        $this->authorize('viewAdminMenu', User::class);

        $dosenIds = array_values(array_filter($request->input('dosen_id', []), fn ($v) => $v !== null && $v !== ''));

        $submission->user->dosenPembimbing()->detach();

        foreach ($dosenIds as $urutan => $dosenId) {
            $submission->user->dosenPembimbing()->attach($dosenId, ['urutan' => $urutan + 1]);
        }

        return redirect()->route('admin.submissions.show', $submission)
            ->with('success', 'Dosen pembimbing berhasil disimpan.');
    }

    public function destroyPembimbing(Request $request, Submission $submission, User $dosen)
    {
        $this->authorize('viewAdminMenu', User::class);

        $urutan = (int) $request->input('urutan', 0);

        $submission->user->dosenPembimbing()->wherePivot('urutan', $urutan)->detach($dosen->id);

        return redirect()->route('admin.submissions.show', $submission)
            ->with('success', 'Dosen pembimbing berhasil dihapus.');
    }
}

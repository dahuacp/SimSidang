<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentFormRequest;
use App\Http\Requests\UpdateAssessmentFormRequest;
use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\Submission;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PenilaianController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sebagaiPenguji = Submission::with(['user', 'schedule.jenisSidang', 'assessmentForms' => fn ($q) => $q
            ->where('dosen_id', $user->id)
            ->where('tipe_penilai', 'penguji')])
            ->whereHas('schedule.dosens', fn ($q) => $q->where('users.id', $user->id))
            ->orderByDesc('created_at')
            ->get();

        $sebagaiPembimbing = Submission::with(['user', 'schedule.jenisSidang', 'assessmentForms' => fn ($q) => $q
            ->where('dosen_id', $user->id)
            ->where('tipe_penilai', 'dospem')])
            ->whereIn('user_id', $user->mahasiswaBimbingan()->pluck('users.id'))
            ->orderByDesc('created_at')
            ->get();

        return view('dosen.penilaian.index', compact('sebagaiPenguji', 'sebagaiPembimbing'));
    }

    public function create(Request $request, Submission $submission)
    {
        $tipe = $request->input('tipe', 'penguji');
        $this->ensureTipeValid($tipe);

        abort_unless(Gate::allows('assess-penilaian', [$submission, $tipe]), 403);

        $template = $this->resolveTemplate($submission);

        if (! $template) {
            return redirect()->route('dosen.penilaian.index')
                ->with('error', 'Template penilaian untuk program studi dan jenis sidang ini belum tersedia.');
        }

        $existing = AssessmentForm::where('submission_id', $submission->id)
            ->where('dosen_id', $request->user()->id)
            ->where('tipe_penilai', $tipe)
            ->first();

        if ($existing) {
            return redirect()->route('dosen.penilaian.edit', $existing);
        }

        return view('dosen.penilaian.create', compact('submission', 'template', 'tipe'));
    }

    public function store(StoreAssessmentFormRequest $request, Submission $submission, NotificationService $notificationService)
    {
        $tipe = $request->tipe_penilai;
        $this->ensureTipeValid($tipe);

        abort_unless(Gate::allows('assess-penilaian', [$submission, $tipe]), 403);

        $template = $this->resolveTemplate($submission);

        if (! $template) {
            return redirect()->route('dosen.penilaian.index')
                ->with('error', 'Template penilaian untuk program studi dan jenis sidang ini belum tersedia.');
        }

        $existing = AssessmentForm::where('submission_id', $submission->id)
            ->where('dosen_id', $request->user()->id)
            ->where('tipe_penilai', $tipe)
            ->first();

        if ($existing) {
            return redirect()->route('dosen.penilaian.edit', $existing);
        }

        $form = AssessmentForm::create([
            'submission_id' => $submission->id,
            'dosen_id' => $request->user()->id,
            'tipe_penilai' => $tipe,
            'template_id' => $template->id,
            'skor_per_item' => $request->skor_per_item,
            'catatan' => $request->catatan,
        ]);

        $notificationService->send(
            $submission->user_id,
            'penilaian.baru',
            ['submission_id' => $submission->id, 'tipe' => $tipe],
            '/mahasiswa/submissions/'.$submission->id.'/penilaian'
        );

        return redirect()->route('dosen.penilaian.edit', $form)
            ->with('success', 'Penilaian berhasil disimpan.');
    }

    public function edit(AssessmentForm $assessmentForm)
    {
        $this->authorize('update', $assessmentForm);

        $assessmentForm->load(['submission.user', 'submission.schedule.jenisSidang', 'template']);

        return view('dosen.penilaian.edit', compact('assessmentForm'));
    }

    public function update(UpdateAssessmentFormRequest $request, AssessmentForm $assessmentForm)
    {
        $this->authorize('update', $assessmentForm);

        $assessmentForm->update([
            'skor_per_item' => $request->skor_per_item,
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('dosen.penilaian.edit', $assessmentForm)
            ->with('success', 'Penilaian berhasil diperbarui.');
    }

    private function ensureTipeValid(string $tipe): void
    {
        abort_unless(in_array($tipe, ['dospem', 'penguji'], true), 422);
    }

    private function resolveTemplate(Submission $submission): ?AssessmentTemplate
    {
        return AssessmentTemplate::where('prodi_id', $submission->user->prodi_id)
            ->where('jenis_sidang_id', $submission->schedule->jenis_sidang_id)
            ->first();
    }
}

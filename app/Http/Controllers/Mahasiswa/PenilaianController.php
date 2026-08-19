<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\AssessmentForm;
use App\Models\Submission;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load(['user.prodi.fakultas', 'schedule.jenisSidang', 'assessmentForms.dosen', 'assessmentForms.template']);

        return view('mahasiswa.penilaian.show', compact('submission'));
    }

    public function cetak(Request $request, Submission $submission, AssessmentForm $assessmentForm)
    {
        abort(403, 'Mahasiswa tidak dapat mencetak lembar penilaian.');
    }
}

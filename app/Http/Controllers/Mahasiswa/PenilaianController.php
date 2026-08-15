<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load(['user', 'schedule.jenisSidang', 'assessmentForms.dosen', 'assessmentForms.template']);

        return view('mahasiswa.penilaian.show', compact('submission'));
    }
}

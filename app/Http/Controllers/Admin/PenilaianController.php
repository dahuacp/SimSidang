<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentForm;
use App\Models\Submission;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function cetakIndex(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');

        $submissions = Submission::with(['user.prodi.fakultas', 'schedule', 'assessmentForms.dosen', 'assessmentForms.template'])
            ->whereHas('assessmentForms')
            ->when($search, fn ($q, $s) => $q
                ->where('judul_laporan', 'like', "%$s%")
                ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rekap.cetak-penilaian', compact('submissions', 'search'));
    }

    public function cetak(Submission $submission, AssessmentForm $assessmentForm)
    {
        $this->authorize('viewAdminMenu', User::class);

        $assessmentForm->load([
            'submission.user.prodi.fakultas',
            'submission.schedule.jenisSidang',
            'dosen',
            'template',
        ]);

        $dospem = $assessmentForm->submission->user->dosenPembimbingByUrutan ?? collect();

        return Pdf::loadView('penilaian.cetak', [
            'assessmentForm' => $assessmentForm,
            'dospem' => $dospem,
            'university' => config('university'),
        ])
            ->setPaper('a4', 'landscape')
            ->stream('penilaian_'.$assessmentForm->submission->user->username.'.pdf');
    }
}

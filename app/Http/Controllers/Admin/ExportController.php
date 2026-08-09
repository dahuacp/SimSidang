<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RekapSubmissionsExport;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function excel()
    {
        $this->authorize('viewAdminMenu', User::class);

        return Excel::download(new RekapSubmissionsExport, 'rekap_submissions_'.now()->format('Ymd_His').'.xlsx');
    }

    public function pdf(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');

        $submissions = Submission::with(['user', 'schedule'])
            ->when($search, fn ($q, $s) => $q->where('judul_laporan', 'like', "%$s%")->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")))
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $pdf = Pdf::loadView('admin.rekap.pdf', compact('submissions', 'search'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('rekap_submissions_'.now()->format('Ymd_His').'.pdf');
    }
}

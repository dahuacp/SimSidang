<?php

namespace App\Http\Controllers\Admin;

use App\Exports\NilaiRekapExport;
use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use App\Services\RekapNilaiService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    public function index(Request $request, RekapNilaiService $service)
    {
        $this->authorize('viewAdminMenu', User::class);

        $prodis = Prodi::all();
        $prodiId = $request->input('prodi_id');
        $sort = $request->input('sort', 'desc');

        $rows = $service->getRows($prodiId, $sort);
        $chartData = $service->getChartData($prodiId);

        return view('admin.rekap.nilai', compact('prodis', 'prodiId', 'sort', 'rows', 'chartData'));
    }

    public function exportExcel(Request $request, RekapNilaiService $service)
    {
        $this->authorize('viewAdminMenu', User::class);

        $prodiId = $request->input('prodi_id');
        $sort = $request->input('sort', 'desc');
        $rows = $service->getRows($prodiId, $sort);

        return Excel::download(new NilaiRekapExport($rows), 'rekap_nilai_'.now()->format('Ymd_His').'.xlsx');
    }
}

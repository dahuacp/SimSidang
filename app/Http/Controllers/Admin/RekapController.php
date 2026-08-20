<?php

namespace App\Http\Controllers\Admin;

use App\Exports\NilaiRekapExport;
use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\JenisSidang;
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

        $filters = $this->filters($request);
        $fakultas = Fakultas::all();
        $prodis = Prodi::all();
        $jenisSidangs = JenisSidang::all();
        $sort = $request->input('sort', 'desc');

        $rows = $service->getRows($filters, $sort);
        $chartData = $service->getChartData($filters);

        return view('admin.rekap.nilai', compact('fakultas', 'prodis', 'jenisSidangs', 'filters', 'sort', 'rows', 'chartData'));
    }

    public function exportExcel(Request $request, RekapNilaiService $service)
    {
        $this->authorize('viewAdminMenu', User::class);

        $sort = $request->input('sort', 'desc');
        $rows = $service->getRows($this->filters($request), $sort);

        return Excel::download(new NilaiRekapExport($rows), 'rekap_nilai_'.now()->format('Ymd_His').'.xlsx');
    }

    protected function filters(Request $request): array
    {
        return $request->only(['fakultas_id', 'prodi_id', 'jenis_sidang_id', 'start_date', 'end_date']);
    }
}

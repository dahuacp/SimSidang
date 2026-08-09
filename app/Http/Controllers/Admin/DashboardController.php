<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RevisionNote;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'mahasiswa' => User::where('role', 'mahasiswa')->count(),
            'dosen' => User::where('role', 'dosen')->count(),
            'submissions' => Submission::count(),
            'revisi_terbuka' => RevisionNote::where('status_poin', 'open')->count(),
        ];

        $schedules = Schedule::withCount('submissions')->orderBy('tanggal_sidang')->get();

        return view('admin.dashboard', compact('stats', 'schedules'));
    }
}

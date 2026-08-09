<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RevisionNote;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\SubmissionStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

        $submissionStatus = Submission::groupBy('status')
            ->pluck(DB::raw('count(*)'), 'status')
            ->toArray();

        $scheduleSubmissions = Schedule::withCount('submissions')
            ->orderBy('tanggal_sidang')
            ->get();

        $revisionStats = RevisionNote::groupBy('status_poin')
            ->pluck(DB::raw('count(*)'), 'status_poin')
            ->toArray();

        $statusTrend = SubmissionStatusLog::selectRaw('DATE(created_at) as date, status_baru, count(*) as total')
            ->groupBy('date', 'status_baru')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($items) => $items->pluck('total', 'status_baru'))
            ->toArray();

        return view('admin.dashboard', compact(
            'stats',
            'schedules',
            'submissionStatus',
            'scheduleSubmissions',
            'revisionStats',
            'statusTrend'
        ));
    }
}

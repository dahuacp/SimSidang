<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant\Tools;

use App\Services\VirtualAssistant\AssistantToolInterface;
use Illuminate\Support\Facades\DB;

class GetScheduleSummaryTool implements AssistantToolInterface
{
    public function name(): string
    {
        return 'getScheduleSummary';
    }

    public function description(): string
    {
        return 'Dapatkan ringkasan keseluruhan jadwal sidang: total jadwal, total mahasiswa terdaftar, total submission, dan distribusi status submission.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $totalJadwal = DB::table('schedules')->count();

        $totalMahasiswa = DB::table('schedule_dosen')
            ->distinct()
            ->count('user_id');

        $submissionStats = DB::table('submissions')
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->toArray();

        return [
            'total_jadwal' => (int) $totalJadwal,
            'total_submission' => array_sum((array) $submissionStats),
            'distribusi_status' => [
                'pending' => (int) ($submissionStats['pending'] ?? 0),
                'sidang_berjalan' => (int) ($submissionStats['sidang_berjalan'] ?? 0),
                'revisi' => (int) ($submissionStats['revisi'] ?? 0),
                'selesai' => (int) ($submissionStats['selesai'] ?? 0),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant\Tools;

use App\Services\VirtualAssistant\AssistantToolInterface;
use Illuminate\Support\Facades\DB;

class GetStudentProgressTool implements AssistantToolInterface
{
    public function name(): string
    {
        return 'getStudentProgress';
    }

    public function description(): string
    {
        return 'Dapatkan statistik progres mahasiswa per status submission dan jumlah poin revisi terbuka. Berdasarkan agregat data — bukan data mentah per mahasiswa.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'schedule_id' => [
                    'type' => 'integer',
                    'description' => 'ID jadwal tertentu. Jika disediakan, filter hanya submission pada jadwal ini. Jika kosong, semua jadwal.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $query = DB::table('submissions as s')
            ->select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status');

        if (isset($arguments['schedule_id'])) {
            $query->where('schedule_id', $arguments['schedule_id']);
        }

        $statusCounts = $query->pluck('jumlah', 'status')->toArray();

        $revisionQuery = DB::table('revision_notes as rn')
            ->join('submissions as s', 'rn.submission_id', '=', 's.id')
            ->select(DB::raw('COUNT(*) as total_open'))
            ->where('rn.status_poin', 'open');

        if (isset($arguments['schedule_id'])) {
            $revisionQuery->where('s.schedule_id', $arguments['schedule_id']);
        }

        $openRevisions = $revisionQuery->value('total_open') ?? 0;

        return [
            'status_submission' => [
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'sidang_berjalan' => (int) ($statusCounts['sidang_berjalan'] ?? 0),
                'revisi' => (int) ($statusCounts['revisi'] ?? 0),
                'selesai' => (int) ($statusCounts['selesai'] ?? 0),
            ],
            'poin_revisi_terbuka' => (int) $openRevisions,
            'total_submission' => array_sum((array) $statusCounts),
        ];
    }
}

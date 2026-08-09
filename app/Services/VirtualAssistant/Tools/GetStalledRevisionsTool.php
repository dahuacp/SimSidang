<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant\Tools;

use App\Services\VirtualAssistant\AssistantToolInterface;
use Illuminate\Support\Facades\DB;

class GetStalledRevisionsTool implements AssistantToolInterface
{
    public function name(): string
    {
        return 'getStalledRevisions';
    }

    public function description(): string
    {
        return 'Dapatkan statistik revisi yang terjebak: jumlah poin revisi dengan status open yang sudah lama (default > 7 hari), rata-rata usia poin open, dan distribusi per grup/ruang.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'hari' => [
                    'type' => 'integer',
                    'description' => 'Ambang batas umur poin open dalam hari. Default: 7.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $hari = $arguments['hari'] ?? 7;

        $cut = now()->subDays($hari);

        $stats = DB::table('revision_notes as rn')
            ->join('submissions as s', 'rn.submission_id', '=', 's.id')
            ->join('schedules as sc', 's.schedule_id', '=', 'sc.id')
            ->selectRaw('COUNT(*) as total_stuck')
            ->where('rn.status_poin', 'open')
            ->where('rn.created_at', '<', $cut)
            ->first();

        $avgRaw = DB::getDriverName() === 'sqlite'
            ? DB::raw("AVG((julianday('now') - julianday(rn.created_at))) as rata_rata_hari")
            : DB::raw('AVG(DATEDIFF(NOW(), rn.created_at)) as rata_rata_hari');

        $rata = DB::table('revision_notes as rn')
            ->join('submissions as s', 'rn.submission_id', '=', 's.id')
            ->select($avgRaw)
            ->where('rn.status_poin', 'open')
            ->where('rn.created_at', '<', $cut)
            ->first()
            ->rata_rata_hari;

        $byGroup = DB::table('revision_notes as rn')
            ->join('submissions as s', 'rn.submission_id', '=', 's.id')
            ->join('schedules as sc', 's.schedule_id', '=', 'sc.id')
            ->select([
                'sc.nama_grup_sidang',
                'sc.ruangan',
                DB::raw('COUNT(*) as jumlah'),
            ])
            ->where('rn.status_poin', 'open')
            ->where('rn.created_at', '<', $cut)
            ->groupBy('sc.nama_grup_sidang', 'sc.ruangan')
            ->get()
            ->map(fn ($item) => [
                'grup' => $item->nama_grup_sidang,
                'ruangan' => $item->ruangan,
                'jumlah_poin' => (int) $item->jumlah,
            ])
            ->toArray();

        return [
            'batas_hari' => $hari,
            'total_poin_terjebak' => (int) ($stats->total_stuck ?? 0),
            'rata_rata_hari' => round((float) ($rata ?? 0), 1),
            'distribusi_per_grup' => $byGroup,
        ];
    }
}

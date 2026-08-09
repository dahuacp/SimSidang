<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant\Tools;

use App\Services\VirtualAssistant\AssistantToolInterface;
use Illuminate\Support\Facades\DB;

class GetDosenWorkloadTool implements AssistantToolInterface
{
    public function name(): string
    {
        return 'getDosenWorkload';
    }

    public function description(): string
    {
        return 'Dapatkan beban kerja tiap dosen: jumlah jadwal yang ditugaskan, jumlah submission aktif (sedang berlangsung/revisi), dan jumlah poin revisi yang belum resolved.';
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
        $dosen = DB::table('users as u')
            ->leftJoin('schedule_dosen as sd', 'u.id', '=', 'sd.user_id')
            ->leftJoin('schedules as sc', 'sd.schedule_id', '=', 'sc.id')
            ->leftJoin('submissions as s', 'sc.id', '=', 's.schedule_id')
            ->leftJoin('revision_notes as rn', 's.id', '=', 'rn.submission_id')
            ->select([
                'u.id',
                'u.name',
                DB::raw('COUNT(DISTINCT sc.id) as jumlah_jadwal'),
                DB::raw('COUNT(DISTINCT s.id) as submissions_aktif'),
                DB::raw('COUNT(rn.id) as total_poin'),
            ])
            ->where('u.role', 'dosen')
            ->where('s.status', '!=', 'selesai')
            ->groupBy('u.id', 'u.name')
            ->get()
            ->map(fn ($item) => [
                'nama_dosen' => $item->name,
                'jumlah_jadwal' => (int) $item->jumlah_jadwal,
                'submissions_aktif' => (int) $item->submissions_aktif,
                'total_poin_revisi' => (int) $item->total_poin,
            ])
            ->toArray();

        return [
            'total_dosen' => count($dosen),
            'dosen_beban' => $dosen,
        ];
    }
}

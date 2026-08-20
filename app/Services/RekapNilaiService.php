<?php

namespace App\Services;

use App\Models\AssessmentForm;
use Illuminate\Support\Collection;

class RekapNilaiService
{
    public function getRows(array $filters = [], string $sort = 'desc'): array
    {
        return $this->computeRows($filters, $sort)['rows'];
    }

    public function getChartData(array $filters = []): array
    {
        $data = $this->computeRows($filters, 'desc');

        return $data['chart'];
    }

    protected function computeRows(array $filters, string $sort): array
    {
        $weight = config('penilaian.bobot');
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $forms = AssessmentForm::with([
            'submission.user.prodi',
            'submission.schedule.jenisSidang',
            'dosen',
            'template',
        ])
            ->whereHas('submission')
            ->when($filters['prodi_id'] ?? null, fn ($q, $id) => $q->whereHas('submission.user', fn ($q2) => $q2->where('prodi_id', $id)))
            ->when($filters['fakultas_id'] ?? null, fn ($q, $id) => $q->whereHas('submission.user.prodi', fn ($q2) => $q2->where('fakultas_id', $id)))
            ->when($filters['jenis_sidang_id'] ?? null, fn ($q, $id) => $q->whereHas('submission.schedule', fn ($q2) => $q2->where('jenis_sidang_id', $id)))
            ->when($startDate || $endDate, fn ($q) => $q->whereHas('submission.schedule', function ($q2) use ($startDate, $endDate) {
                $dates = array_filter([$startDate, $endDate]);
                count($dates) === 1
                    ? $q2->whereDate('tanggal_sidang', $dates[0])
                    : $q2->whereBetween('tanggal_sidang', $dates);
            }))
            ->get()
            ->groupBy('submission_id');

        $rows = [];
        foreach ($forms as $submissionId => $formGroup) {
            $submission = $formGroup->first()->submission;
            $user = $submission->user;

            $dospemScore = $this->calculateAverageScore($formGroup, 'dospem');
            $pengujiScore = $this->calculateAverageScore($formGroup, 'penguji');

            $totalScore = 0;
            if ($dospemScore > 0 && $pengujiScore > 0) {
                $totalScore = round($dospemScore * ($weight['dospem'] / 100) + $pengujiScore * ($weight['penguji'] / 100), 1);
            } elseif ($dospemScore > 0) {
                $totalScore = $dospemScore;
            } elseif ($pengujiScore > 0) {
                $totalScore = $pengujiScore;
            }

            $rows[] = [
                'no' => 0,
                'mahasiswa' => $user->name,
                'nim' => $user->username,
                'prodi' => $user->prodi?->nama_prodi ?? '-',
                'judul' => $submission->judul_laporan,
                'dospem_nilai' => $dospemScore > 0 ? $dospemScore : '-',
                'penguji_nilai' => $pengujiScore > 0 ? $pengujiScore : '-',
                'nilai_akhir' => $totalScore,
            ];
        }

        $sortedRows = collect($rows)->sortBy('nilai_akhir', SORT_NUMERIC, $sort === 'asc')->values()->toArray();
        $finalRows = array_map(fn ($row, $idx) => array_replace($row, ['no' => $idx + 1]), $sortedRows, array_keys($sortedRows));

        $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
        foreach ($finalRows as $row) {
            $score = $row['nilai_akhir'];
            if ($score >= 80) {
                $distribution['A']++;
            } elseif ($score >= 70) {
                $distribution['B']++;
            } elseif ($score >= 60) {
                $distribution['C']++;
            } elseif ($score >= 50) {
                $distribution['D']++;
            } else {
                $distribution['E']++;
            }
        }

        $perProdi = collect($finalRows)->groupBy('prodi')->map(fn ($items, $prodi) => [
            'nama' => $prodi,
            'rata_rata' => round($items->avg('nilai_akhir'), 1),
            'jumlah' => $items->count(),
        ])->values()->toArray();

        return [
            'rows' => $finalRows,
            'chart' => [
                'distribution' => $distribution,
                'perProdi' => $perProdi,
            ],
        ];
    }

    protected function calculateAverageScore(Collection $formGroup, string $tipe): float
    {
        $filtered = $formGroup->where('tipe_penilai', $tipe);
        if ($filtered->isEmpty()) {
            return 0.0;
        }

        return round($filtered->average('skor_total'), 1);
    }
}

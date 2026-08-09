<?php

namespace App\Exports;

use App\Models\Submission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapSubmissionsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Submission::with(['user', 'schedule', 'revisionNotes'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Submission $s) {
                $open = $s->revisionNotes->where('status_poin', 'open')->count();
                $resolved = $s->revisionNotes->where('status_poin', 'resolved')->count();

                return [
                    $s->id,
                    $s->user->name,
                    $s->user->username,
                    $s->schedule ? $s->schedule->nama_grup_sidang : '-',
                    $s->judul_laporan,
                    $s->status,
                    $open,
                    $resolved,
                ];
            });
    }

    public function headings(): array
    {
        return ['ID', 'Mahasiswa', 'NIM', 'Grup Sidang', 'Judul', 'Status', 'Poin Open', 'Poin Resolved'];
    }
}

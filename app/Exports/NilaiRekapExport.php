<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NilaiRekapExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $rows) {}

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return ['No', 'Mahasiswa', 'NIM', 'Progdi', 'Judul Laporan', 'Dospem', 'Penguji', 'Nilai Akhir'];
    }

    public function map($row): array
    {
        return [
            $row['no'],
            $row['mahasiswa'],
            $row['nim'],
            $row['prodi'],
            $row['judul'],
            $row['dospem_nilai'],
            $row['penguji_nilai'],
            $row['nilai_akhir'],
        ];
    }
}

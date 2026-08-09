<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ScheduleImport implements ToCollection, WithHeadingRow, WithValidation
{
    public $failures = [];

    public function collection(Collection $rows): void
    {
        $dosenIds = User::where('role', 'dosen')->pluck('id')->toArray();

        foreach ($rows as $i => $row) {
            try {
                DB::beginTransaction();

                $schedule = Schedule::create([
                    'nama_grup_sidang' => $row['nama_grup_sidang'],
                    'ruangan' => $row['ruangan'],
                    'tanggal_sidang' => Carbon::parse($row['tanggal_sidang'])->toDateString(),
                    'jam_mulai' => Carbon::parse($row['jam_mulai'])->format('H:i'),
                    'jam_selesai' => Carbon::parse($row['jam_selesai'])->format('H:i'),
                ]);

                $dosenCol = $row['dosen_ids'] ?? null;
                if ($dosenCol) {
                    $ids = array_filter(array_map('trim', explode(',', $dosenCol)));
                    $valid = array_intersect($ids, $dosenIds);
                    if ($valid) {
                        $schedule->dosens()->sync($valid);
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->failures[] = 'Baris '.($i + 2).': '.$e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            'nama_grup_sidang' => 'required|string',
            'ruangan' => 'required|string',
            'tanggal_sidang' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i',
        ];
    }

    public function headings(): array
    {
        return ['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai', 'dosen_ids'];
    }
}

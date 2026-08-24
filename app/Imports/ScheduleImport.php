<?php

namespace App\Imports;

use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleConflictService;
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
        $service = app(ScheduleConflictService::class);
        $accepted = [];

        foreach ($rows as $i => $row) {
            try {
                DB::beginTransaction();

                $jenisSidang = null;
                $jenisName = trim((string) ($row['jenis_sidang'] ?? ''));
                if ($jenisName !== '') {
                    $jenisSidang = JenisSidang::where('nama', $jenisName)->first();
                    if (! $jenisSidang) {
                        throw new \Exception("Jenis sidang \"{$jenisName}\" tidak ditemukan.");
                    }
                }

                $tanggal = Carbon::parse($row['tanggal_sidang'])->toDateString();
                $jamMulai = Carbon::parse($row['jam_mulai'])->format('H:i');
                $jamSelesai = Carbon::parse($row['jam_selesai'])->format('H:i');

                $candidateIds = [];
                $dosenCol = $row['dosen_ids'] ?? null;
                if ($dosenCol) {
                    $ids = array_filter(array_map('trim', explode(',', $dosenCol)));
                    $candidateIds = array_values(array_intersect($ids, $dosenIds));
                }

                $this->guardConflicts($service, $candidateIds, $tanggal, $jamMulai, $jamSelesai, $accepted);

                $schedule = Schedule::create([
                    'nama_grup_sidang' => $row['nama_grup_sidang'],
                    'ruangan' => $row['ruangan'],
                    'tanggal_sidang' => $tanggal,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'jenis_sidang_id' => $jenisSidang?->id,
                ]);

                if ($candidateIds) {
                    $schedule->dosens()->sync($candidateIds);
                }

                DB::commit();

                $accepted[] = [
                    'tanggal' => $tanggal,
                    'mulai' => $jamMulai,
                    'selesai' => $jamSelesai,
                    'dosen_ids' => $candidateIds,
                ];
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->failures[] = 'Baris '.($i + 2).': '.$e->getMessage();
            }
        }
    }

    private function guardConflicts(ScheduleConflictService $service, array $candidateIds, string $tanggal, string $jamMulai, string $jamSelesai, array $accepted): void
    {
        if ($candidateIds === []) {
            return;
        }

        $conflicts = $service->findDosenConflicts($candidateIds, $tanggal, $jamMulai, $jamSelesai);

        foreach ($conflicts as $entry) {
            foreach ($service->describeConflict($entry) as $message) {
                throw new \Exception($message);
            }
        }

        foreach ($accepted as $row) {
            if ($row['tanggal'] === $tanggal
                && $row['mulai'] < $jamSelesai
                && $row['selesai'] > $jamMulai
                && count(array_intersect($row['dosen_ids'], $candidateIds)) > 0
            ) {
                throw new \Exception('Konflik dengan baris lain dalam file import (jadwal overlap dengan dosen yang sama).');
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
        return ['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai', 'jenis_sidang', 'dosen_ids'];
    }
}

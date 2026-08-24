<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    public function findDosenConflicts(array $dosenIds, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection
    {
        return $this->findConflicts($dosenIds, 'dosens', $tanggalSidang, $jamMulai, $jamSelesai, $ignoreScheduleId);
    }

    public function findMahasiswaConflicts(array $userIds, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId = null): Collection
    {
        return $this->findConflicts($userIds, 'mahasiswas', $tanggalSidang, $jamMulai, $jamSelesai, $ignoreScheduleId);
    }

    /**
     * @param  array{user: User, schedules: Collection<int, Schedule>}  $entry
     * @return list<string>
     */
    public function describeConflict(array $entry): array
    {
        return collect($entry['schedules'])
            ->map(fn (Schedule $schedule): string => sprintf(
                '%s (%s) sudah ada di jadwal "%s" pada %s pukul %s-%s.',
                $entry['user']->name,
                $entry['user']->username,
                $schedule->nama_grup_sidang,
                $schedule->tanggal_sidang->translatedFormat('d/m/Y'),
                $schedule->jam_mulai->format('H:i'),
                $schedule->jam_selesai->format('H:i'),
            ))
            ->all();
    }

    private function findConflicts(array $userIds, string $relation, string $tanggalSidang, string $jamMulai, string $jamSelesai, ?int $ignoreScheduleId): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        $schedules = Schedule::query()
            ->whereDate('tanggal_sidang', $tanggalSidang)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->when($ignoreScheduleId !== null, fn ($query) => $query->whereKeyNot($ignoreScheduleId))
            ->whereHas($relation, fn ($query) => $query->whereIn('users.id', $userIds))
            ->with([$relation => fn ($query) => $query->whereIn('users.id', $userIds)])
            ->get();

        $result = collect();

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $conflicting = $schedules
                ->filter(fn (Schedule $schedule): bool => $schedule->{$relation}->contains('id', $user->id))
                ->values();

            if ($conflicting->isNotEmpty()) {
                $result->put($user->id, [
                    'user' => $user,
                    'schedules' => $conflicting,
                ]);
            }
        }

        return $result;
    }
}

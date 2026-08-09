<?php

namespace Database\Factories;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_grup_sidang' => 'Sidang TA Gelombang '.fake()->randomDigitNotNull(),
            'ruangan' => 'Ruang '.fake()->randomElement(['Lab Komputer 3', 'Seminar 2', 'Sidang A']),
            'tanggal_sidang' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ];
    }
}

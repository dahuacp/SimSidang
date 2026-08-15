<?php

namespace Database\Factories;

use App\Models\JenisSidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisSidang>
 */
class JenisSidangFactory extends Factory
{
    private static int $counter = 1;

    public function definition(): array
    {
        $list = [
            ['nama' => 'TA', 'deskripsi' => 'Tugas Akhir'],
            ['nama' => 'KP', 'deskripsi' => 'Kerja Praktek'],
            ['nama' => 'Milestone Design', 'deskripsi' => 'Fase perencanaan dan desain'],
        ];

        $selected = $list[(self::$counter - 1) % count($list)];
        self::$counter++;

        return [
            'nama' => $selected['nama'].' '.self::$counter,
            'deskripsi' => $selected['deskripsi'],
        ];
    }
}

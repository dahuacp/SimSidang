<?php

namespace Database\Factories;

use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prodi>
 */
class ProdiFactory extends Factory
{
    private static int $counter = 1;

    public function definition(): array
    {
        $list = [
            ['kode' => 'TI', 'nama' => 'Teknik Informatika'],
            ['kode' => 'SI', 'nama' => 'Sistem Informasi'],
            ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual'],
            ['kode' => 'MBTI', 'nama' => 'Manajemen Bisnis dan Teknologi Informasi'],
            ['kode' => 'ARS', 'nama' => 'Arsitektur'],
            ['kode' => 'PSDK', 'nama' => 'Psikologi'],
            ['kode' => 'AK', 'nama' => 'Akuntansi'],
            ['kode' => 'MN', 'nama' => 'Manajemen'],
            ['kode' => 'IL', 'nama' => 'Ilmu Hukum'],
            ['kode' => 'EL', 'nama' => 'Elektro'],
        ];

        $index = self::$counter % count($list);
        self::$counter++;

        $selected = $list[$index];

        return [
            'kode_prodi' => $selected['kode'],
            'nama_prodi' => $selected['nama'],
        ];
    }
}

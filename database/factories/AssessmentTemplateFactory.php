<?php

namespace Database\Factories;

use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentTemplate>
 */
class AssessmentTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prodi_id' => Prodi::factory(),
            'jenis_sidang_id' => JenisSidang::factory(),
            'nama' => fake()->unique()->sentence(2),
            'nilai_penyebut' => 15,
            'nilai_pengali' => 100,
            'items' => [
                ['name' => 'Kualitas Laporan', 'maksimal' => 5, 'urutan' => 1],
                ['name' => 'Penguasaan Materi', 'maksimal' => 5, 'urutan' => 2],
                ['name' => 'Presentasi', 'maksimal' => 5, 'urutan' => 3],
            ],
        ];
    }
}

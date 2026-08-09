<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'schedule_id' => Schedule::factory(),
            'judul_laporan' => fake()->sentence(5),
            'file_path' => 'submissions/laporan.pdf',
            'status' => 'pending',
        ];
    }
}

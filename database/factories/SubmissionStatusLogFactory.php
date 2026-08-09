<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionStatusLog>
 */
class SubmissionStatusLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'status_lama' => 'pending',
            'status_baru' => 'sidang_berjalan',
            'diubah_oleh' => User::factory()->dosen(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\RevisionNote;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevisionNote>
 */
class RevisionNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'dosen_id' => User::factory()->dosen(),
            'catatan_revisi' => fake()->sentence(),
            'status_poin' => 'open',
        ];
    }
}

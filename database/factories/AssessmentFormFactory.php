<?php

namespace Database\Factories;

use App\Models\AssessmentForm;
use App\Models\AssessmentTemplate;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentForm>
 */
class AssessmentFormFactory extends Factory
{
    public function definition(): array
    {
        $template = AssessmentTemplate::factory()->create();

        $skorPerItem = [];
        foreach ($template->items as $idx => $item) {
            $skorPerItem[] = ['item' => $idx, 'skor' => fake()->numberBetween(1, $item['maksimal'])];
        }

        return [
            'submission_id' => Submission::factory(),
            'dosen_id' => User::factory()->dosen(),
            'tipe_penilai' => fake()->randomElement(['dospem', 'penguji']),
            'template_id' => $template->id,
            'skor_per_item' => $skorPerItem,
            'catatan' => fake()->sentence(),
        ];
    }
}

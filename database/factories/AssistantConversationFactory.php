<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssistantConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantConversation>
 */
class AssistantConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'judul' => $this->faker->sentence(4),
        ];
    }
}

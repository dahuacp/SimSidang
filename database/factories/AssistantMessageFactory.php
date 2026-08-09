<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantMessage>
 */
class AssistantMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => AssistantConversation::factory(),
            'role' => $this->faker->randomElement(['user', 'assistant']),
            'content' => $this->faker->paragraph,
            'tool_calls' => null,
        ];
    }

    public function userMessage(): static
    {
        return $this->state(fn () => ['role' => 'user']);
    }

    public function assistantMessage(): static
    {
        return $this->state(fn () => ['role' => 'assistant']);
    }

    public function withToolCalls(array $toolCalls): static
    {
        return $this->state(fn () => [
            'tool_calls' => $toolCalls,
        ]);
    }
}

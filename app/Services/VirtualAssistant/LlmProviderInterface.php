<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

interface LlmProviderInterface
{
    public function chat(array $messages, array $tools = []): array;
}

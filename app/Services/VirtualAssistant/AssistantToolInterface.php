<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

interface AssistantToolInterface
{
    public function name(): string;

    public function description(): string;

    public function parameters(): array;

    public function execute(array $arguments): array;
}

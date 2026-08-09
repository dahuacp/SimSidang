<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

use Illuminate\Support\Facades\Http;

class OpenAiCompatibleProvider implements LlmProviderInterface
{
    protected string $url;

    protected string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->url = config('assistant.llm.url', 'https://api.openai.com/v1');
        $this->apiKey = config('assistant.llm.api_key', '');
        $this->model = config('assistant.llm.model', 'gpt-4');
    }

    public function chat(array $messages, array $tools = []): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => 'auto',
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post(rtrim($this->url, '/').'/chat/completions', $payload);

        if ($response->failed()) {
            return [
                'error' => true,
                'message' => $response->json('error.message', 'Gagal menghubungi LLM provider'),
            ];
        }

        return $response->json();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\VirtualAssistant;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Services\VirtualAssistant\Tools\GetDosenWorkloadTool;
use App\Services\VirtualAssistant\Tools\GetScheduleSummaryTool;
use App\Services\VirtualAssistant\Tools\GetStalledRevisionsTool;
use App\Services\VirtualAssistant\Tools\GetStudentProgressTool;
use App\Services\VirtualAssistant\Tools\QueryDataTool;
use App\Services\VirtualAssistant\Tools\RunSqlQueryTool;
use Illuminate\Support\Collection;

class AssistantService
{
    protected LlmProviderInterface $llm;

    protected array $tools;

    public function __construct(LlmProviderInterface $llm)
    {
        $this->llm = $llm;
        $this->tools = [
            new GetStudentProgressTool,
            new GetDosenWorkloadTool,
            new GetStalledRevisionsTool,
            new GetScheduleSummaryTool,
        ];

        if (config('assistant.query.enabled', true)) {
            $guard = app(ReadOnlyGuard::class);
            $catalog = app(SchemaCatalog::class);

            $this->tools[] = new QueryDataTool($guard, $catalog);

            if (config('assistant.query.raw_sql_enabled', true)) {
                $this->tools[] = new RunSqlQueryTool($guard);
            }
        }
    }

    public function getToolsSchema(): array
    {
        return collect($this->tools)->map(fn (AssistantToolInterface $tool) => [
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => $tool->parameters(),
            ],
        ])->values()->toArray();
    }

    public function getToolMap(): array
    {
        return collect($this->tools)->keyBy(fn (AssistantToolInterface $tool) => $tool->name())
            ->mapWithKeys(fn (AssistantToolInterface $tool) => [$tool->name() => $tool])
            ->toArray();
    }

    public function getMessages(int $conversationId): Collection
    {
        return AssistantMessage::where('conversation_id', $conversationId)
            ->orderBy('id')
            ->get();
    }

    public function sendMessage(string $conversationId, string $message): array
    {
        $conversation = AssistantConversation::findOrFail($conversationId);

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $messages = $this->buildMessageContext($conversation);

        $toolMap = $this->getToolMap();
        $tools = $this->getToolsSchema();
        $maxIterations = 5;
        $iteration = 0;
        $toolResults = [];

        while ($iteration < $maxIterations) {
            $response = $this->llm->chat($messages, $tools);

            if (isset($response['error']) && $response['error'] === true) {
                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => 'Maaf, terjadi kesalahan saat menghubungi server AI: '.$response['message'],
                ]);

                return $this->formatResponse($assistantMessage, $toolResults);
            }

            $choice = $response['choices'][0] ?? null;
            $assistantResponse = $choice['message'] ?? [];

            $content = $assistantResponse['content'] ?? null;
            $toolCalls = $assistantResponse['tool_calls'] ?? null;

            if (! empty($toolCalls)) {
                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $content ?? '',
                    'tool_calls' => $this->serializeToolCalls($toolCalls),
                ]);

                foreach ($toolCalls as $toolCall) {
                    $toolName = $toolCall['function']['name'] ?? null;
                    $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true);

                    if (! isset($toolMap[$toolName])) {
                        continue;
                    }

                    $toolResult = $toolMap[$toolName]->execute($toolArgs ?? []);

                    $toolResults[] = [
                        'tool' => $toolName,
                        'arguments' => $toolArgs,
                        'result' => $toolResult,
                    ];

                    $messages[] = [
                        'role' => 'assistant',
                        'content' => $content,
                        'tool_calls' => $toolCalls,
                    ];
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                    ];
                }

                $iteration++;

                continue;
            }

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $content ?? 'Tidak ada respons dari asisten.',
            ]);

            return $this->formatResponse($assistantMessage, $toolResults);
        }

        $fallbackMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Saya telah mencapai batas maksimum pemanggilan tool. Silakan ajukan pertanyaan yang lebih spesifik.',
        ]);

        return $this->formatResponse($fallbackMessage, $toolResults);
    }

    public function createConversation(int $adminId): AssistantConversation
    {
        return AssistantConversation::create([
            'admin_id' => $adminId,
            'judul' => null,
        ]);
    }

    public function getConversations(int|string $adminId): Collection
    {
        return AssistantConversation::where('admin_id', $adminId)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getConversation(string $conversationId, int|string $adminId): AssistantConversation
    {
        return AssistantConversation::where('admin_id', $adminId)
            ->with('messages')
            ->findOrFail($conversationId);
    }

    public function autoGenerateTitle(string $conversationId): void
    {
        $conversation = AssistantConversation::findOrFail($conversationId);

        if ($conversation->judul !== null) {
            return;
        }

        $firstUserMessage = $conversation->messages()
            ->where('role', 'user')
            ->orderBy('id')
            ->value('content');

        if ($firstUserMessage) {
            $conversation->update([
                'judul' => substr($firstUserMessage, 0, 50).(strlen($firstUserMessage) > 50 ? '...' : ''),
            ]);
        }
    }

    protected function buildMessageContext(AssistantConversation $conversation): array
    {
        $systemPrompt = config('assistant.llm.system_prompt', '');

        $systemPrompt .= PHP_EOL.PHP_EOL.'Skema database yang tersedia untuk query (read-only):'.PHP_EOL;
        $systemPrompt .= app(SchemaCatalog::class)->schemaDescription();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($conversation->messages as $msg) {
            if ($msg->role === 'user') {
                $messages[] = ['role' => 'user', 'content' => $msg->content];
            } elseif ($msg->role === 'assistant') {
                $messages[] = ['role' => 'assistant', 'content' => $msg->content];
            }
        }

        return $messages;
    }

    protected function serializeToolCalls(array $toolCalls): array
    {
        return collect($toolCalls)->map(fn ($call) => [
            'id' => $call['id'] ?? null,
            'type' => $call['type'] ?? 'function',
            'function' => [
                'name' => $call['function']['name'] ?? null,
                'arguments' => $call['function']['arguments'] ?? null,
            ],
        ])->toArray();
    }

    protected function formatResponse(AssistantMessage $assistantMessage, array $toolResults): array
    {
        return [
            'message' => $assistantMessage->content,
            'tool_calls' => $toolResults,
            'conversation_id' => $assistantMessage->conversation_id,
        ];
    }
}

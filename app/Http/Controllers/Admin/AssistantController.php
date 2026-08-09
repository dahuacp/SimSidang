<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssistantMessageRequest;
use App\Models\AssistantConversation;
use App\Models\User;
use App\Services\VirtualAssistant\AssistantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('use-virtual-assistant', User::class);

        $conversation = AssistantConversation::where('admin_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->first();

        if (! $conversation) {
            $conversation = AssistantConversation::create([
                'admin_id' => $request->user()->id,
            ]);
        }

        return view('admin.assistant.index', [
            'conversationId' => $conversation->id,
            'initialMessages' => $conversation->messages,
        ]);
    }

    public function show(Request $request, string $conversationId): View|JsonResponse
    {
        $this->authorize('use-virtual-assistant', User::class);

        $conversation = AssistantConversation::where('admin_id', auth()->id())
            ->with('messages')
            ->findOrFail($conversationId);

        if ($request->wantsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'messages' => $conversation->messages,
            ]);
        }

        return view('admin.assistant.index', [
            'conversationId' => $conversation->id,
            'initialMessages' => $conversation->messages,
        ]);
    }

    public function chat(
        StoreAssistantMessageRequest $request,
        string $conversationId,
        AssistantService $assistantService
    ): JsonResponse {
        $this->authorize('use-virtual-assistant', User::class);

        $conversation = AssistantConversation::where('admin_id', auth()->id())
            ->findOrFail($conversationId);

        try {
            $result = $assistantService->sendMessage($conversationId, $request->input('content'));

            $assistantService->autoGenerateTitle($conversationId);

            return response()->json([
                'success' => true,
                'response' => $result['message'],
                'tool_calls' => $result['tool_calls'],
                'conversation_id' => $result['conversation_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Assistant chat error', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses permintaan Anda.',
            ], 500);
        }
    }

    public function conversations(Request $request)
    {
        $this->authorize('use-virtual-assistant', User::class);

        $assistantService = app(AssistantService::class);
        $conversations = $assistantService->getConversations(auth()->id());

        return response()->json($conversations);
    }

    public function createNew(Request $request)
    {
        $this->authorize('use-virtual-assistant', User::class);

        $assistantService = app(AssistantService::class);
        $conversation = $assistantService->createConversation(auth()->id());

        return redirect()->route('admin.assistant.show', $conversation->id);
    }
}

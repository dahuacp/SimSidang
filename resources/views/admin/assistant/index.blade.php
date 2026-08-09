@extends('layouts.app')

@section('title', 'Asisten Virtual — SISIDANG')

@section('content')
<div x-data="chatAssistant({ conversationId: @js($conversationId), initialMessages: @js($initialMessages ?? []) })" x-init="init()" class="d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Asisten Virtual Admin</h1>
        <a href="{{ route('admin.assistant.new') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg"></i> Chat Baru
        </a>
    </div>

    <div class="card flex-grow-1 d-flex flex-column" style="max-height: 70vh;">
        <div class="card-body flex-grow-1 overflow-auto" ref="messagesContainer">
            <template x-if="loading && messages.length === 0">
                <div class="text-center text-muted py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 mb-0">Memuat percakapan...</p>
                </div>
            </template>

            <template x-if="!loading && messages.length === 0">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-robot fs-1 mb-3"></i>
                    <p class="mb-1 fw-medium">Hai! Saya Asisten Virtual SIMSIDANG.</p>
                    <p class="small mb-0">
                        Saya bisa membantu menganalisis data sidang secara agregat.<br>
                        Tanyakan tentang progres mahasiswa, beban dosen, revisi terjebak, atau ringkasan jadwal.
                    </p>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div class="d-flex mb-3" :class="msg.role === 'user' ? 'justify-content-end' : 'justify-content-start'">
                    <div class="card border-0 shadow-sm" style="max-width: 75%;">
                        <div class="card-body p-3" :class="msg.role === 'user' ? 'bg-primary bg-opacity-10' : 'bg-body-tertiary'">
                            <div class="small fw-medium mb-2 text-body-secondary">
                                <i :class="msg.role === 'user' ? 'bi bi-person-circle' : 'bi bi-robot'"></i>
                                <span x-text="msg.role === 'user' ? 'Anda' : 'Asisten'"></span>
                                <span class="text-muted ms-2" x-text="formatDate(msg.created_at)"></span>
                            </div>
                            <div class="white-space-pre-wrap" x-html="formatContent(msg.content)"></div>

                            <template x-if="msg.tool_calls && msg.tool_calls.length">
                                <div class="mt-2 small text-muted">
                                    <div class="border rounded p-2 bg-body-secondary" style="font-size: 0.75rem;">
                                        <div class="fw-medium mb-1">Tool yang dipanggil:</div>
                                        <template x-for="tc in msg.tool_calls" :key="tc.tool">
                                            <div>
                                                <span class="fw-medium" x-text="tc.tool"></span>
                                                <span class="text-muted ms-1">→</span>
                                                <pre class="mb-0 mt-1 bg-body-tertiary p-1 rounded" style="font-size: 0.7rem;" x-text="formatToolResult(tc.result)"></pre>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="loading && messages.length > 0">
                <div class="d-flex justify-content-start mb-3">
                    <div class="card border-0 shadow-sm bg-body-secondary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <span>Asisten sedang menjawab...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="card-footer bg-body border-top">
            <form @submit.prevent="sendMessage()" class="d-flex gap-2">
                <textarea x-model="input"
                    @keydown.enter.prevent="sendMessage()"
                    class="form-control"
                    placeholder="Ketik pertanyaan Anda..."
                    :disabled="loading"
                    rows="2"
                    maxlength="2000"
                ></textarea>
                <button type="submit" class="btn btn-primary" :disabled="loading || !input.trim()">
                    <i class="bi" :class="loading ? 'bi-arrow-right-circle' : 'bi-send'"></i>
                </button>
            </form>

            <div x-show="error" class="alert alert-danger alert-dismissible fade show mt-2 mb-0 small">
                <i class="bi bi-exclamation-circle me-1"></i>
                <span x-text="error"></span>
            </div>
        </div>
    </div>
</div>
@endsection

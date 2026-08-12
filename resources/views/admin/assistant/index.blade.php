@extends('layouts.app')

@section('title', 'Asisten Virtual — SISIDANG')

@section('content')
<div x-data="chatAssistant({ conversationId: @js($conversationId), initialMessages: @js($initialMessages ?? []) })" x-init="init()" class="flex flex-col">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Asisten Virtual Admin</h1>
        <a href="{{ route('admin.assistant.new') }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Chat Baru
        </a>
    </div>

    <div class="flex max-h-[70vh] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="flex-1 overflow-y-auto p-5" ref="messagesContainer">
            <template x-if="loading && messages.length === 0">
                <div class="py-5 text-center text-gray-500 dark:text-gray-400">
                    <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-brand-500"></div>
                    <p class="mt-3 mb-0">Memuat percakapan...</p>
                </div>
            </template>

            <template x-if="!loading && messages.length === 0">
                <div class="py-5 text-center text-gray-500 dark:text-gray-400">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-brand-500/10 dark:bg-brand-500/15">
                        <svg class="h-7 w-7 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.8125 2C5.47917 2 2 5.47917 2 9.8125c0 4.334 3.479 7.8125 7.8125 7.8125 1.29 0 2.5 0.305 3.55-0.81l.03-.02c.87-.74 1.22-1.94 1.28-3.13.03-.62.05-1.24.05-1.88"></path>
                            <path fill="currentColor" d="M14 9a5 5 0 105 5 5 5 0 01-5-5z"></path>
                        </svg>
                    </div>
                    <p class="mb-1 font-medium">Hai! Saya Asisten Virtual SIMSIDANG.</p>
                    <p class="text-sm mb-0">
                        Saya bisa membantu menganalisis data sidang secara agregat.<br>
                        Tanyakan tentang progres mahasiswa, beban dosen, revisi terjebak, atau ringkasan jadwal.
                    </p>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div class="mb-3 flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[75%] rounded-2xl border border-gray-200 shadow-sm dark:border-gray-800" :class="msg.role === 'user' ? 'bg-brand-500/5 dark:bg-brand-500/10' : 'bg-gray-50 dark:bg-gray-800'">
                        <div class="p-3">
                            <div class="mb-2 flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <template x-if="msg.role === 'user'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </template>
                                    <template x-if="msg.role !== 'user'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.8125 2C5.47917 2 2 5.47917 2 9.8125c0 4.334 3.479 7.8125 7.8125 7.8125 1.29 0 2.5 0.305 3.55-0.81l.03-.02c.87-.74 1.22-1.94 1.28-3.13.03-.62.05-1.24.05-1.88"></path>
                                    </template>
                                </svg>
                                <span x-text="msg.role === 'user' ? 'Anda' : 'Asisten'"></span>
                                <span class="text-gray-400 dark:text-gray-500 ms-2" x-text="formatDate(msg.created_at)"></span>
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-html="formatContent(msg.content)"></div>

                            <template x-if="msg.tool_calls && msg.tool_calls.length">
                                <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                                    <div class="mb-1 font-medium">Tool yang dipanggil:</div>
                                    <template x-for="tc in msg.tool_calls" :key="tc.tool">
                                        <div>
                                            <span class="font-medium" x-text="tc.tool"></span>
                                            <span class="ms-1 text-gray-400 dark:text-gray-500">→</span>
                                            <pre class="mb-0 mt-1 rounded bg-gray-100 p-1 text-[0.7rem] text-gray-500 dark:bg-gray-800 dark:text-gray-400" x-text="formatToolResult(tc.result)"></pre>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="loading && messages.length > 0">
                <div class="mb-3 flex justify-start">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800">
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-brand-500"></div>
                            <span>Asisten sedang menjawab...</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
            <form @submit.prevent="sendMessage()" class="flex gap-2">
                <textarea x-model="input"
                    @keydown.enter.prevent="sendMessage()"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                    placeholder="Ketik pertanyaan Anda..."
                    :disabled="loading"
                    rows="2"
                    maxlength="2000"
                ></textarea>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 text-white transition hover:bg-brand-600 disabled:opacity-50"
                        :disabled="loading || !input.trim()">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>

            <div x-show="error" class="mt-2 rounded-lg border border-error-500/20 bg-error-50 p-3 text-xs text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                <span x-text="error"></span>
            </div>
        </div>
    </div>
</div>
@endsection

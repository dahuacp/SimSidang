@if($submission->file_path)
    <div x-data="aiRead({
        submitUrl: @js(route('dosen.ai-read', $submission)),
        refreshUrl: @js(route('dosen.ai-read.refresh', $submission)),
        draftUrl: @js(route('dosen.revision-notes.draft', $submission)),
        createUrl: @js(route('dosen.revision-notes.create', $submission)),
        token: @js(csrf_token())
    })">
        <button type="button" @click="analyze()"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand-500 px-4 py-2 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"></path>
            </svg>
            Baca dengan AI
        </button>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
            <div class="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Analisa Laporan dengan AI</h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div x-show="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-brand-500 border-t-transparent"></div>
                    Membaca laporan, mohon tunggu...
                </div>

                <div x-show="!loading && error" class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-300" x-text="error"></div>

                <template x-if="!loading && !error && summary">
                    <div>
                        <div class="mb-4 rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                            <h4 class="mb-1 text-xs font-semibold uppercase text-brand-600 dark:text-brand-400">Ringkasan</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="summary"></p>
                        </div>

                        <h4 class="mb-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Rekomendasi Revisi</h4>
                        <ul class="space-y-2">
                            <template x-for="(point, i) in points" :key="i">
                                <li class="flex gap-3 rounded-lg border border-gray-200 p-3 text-sm text-gray-700 dark:border-gray-800 dark:text-gray-300">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-semibold text-white" x-text="i + 1"></span>
                                    <span x-text="point"></span>
                                </li>
                            </template>
                        </ul>

                        <button type="button" @click="goToRevision()"
                                :disabled="loading"
                                class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Buat Revisi dari Hasil AI
                        </button>

                        <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-800">
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                Model: <span x-text="model"></span>
                                <span x-show="cached" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-800">cache</span>
                            </span>
                            <button type="button" @click="refresh()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Refresh Analisa
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endif

@extends('layouts.app')

@section('title', 'Detail Submission')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Detail Submission</h1>
        <a href="{{ route('admin.submissions.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $submission->judul_laporan ?: '(Belum ada judul)' }}</h2>
        <div class="space-y-2 text-sm">
            <p class="text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-500 dark:text-gray-400">Mahasiswa:</span> {{ $submission->user->name }} ({{ $submission->user->username }})
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-500 dark:text-gray-400">Grup:</span> {{ $submission->schedule->nama_grup_sidang ?? '-' }}
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-500 dark:text-gray-400">Status:</span>
                <x-status-badge :status="$submission->status" />
            </p>
        </div>
        @if($submission->file_path)
            <a href="{{ route('files.submission', $submission) }}"
               class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg border border-brand-500 px-4 py-2 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
                </svg>
                Unduh Laporan
            </a>
        @endif
    </div>

    <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-gray-200">Catatan Revisi</h2>
    @if($submission->revisionNotes->isNotEmpty())
        <div class="space-y-3">
            @foreach($submission->revisionNotes as $note)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2">
                        <strong class="text-sm text-gray-800 dark:text-gray-200">{{ $note->dosen->name ?? '-' }}</strong>
                        <span class="status-pill {{ $note->status_poin === 'open' ? 'badge-open' : 'badge-resolved' }}">{{ ucfirst($note->status_poin) }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $note->catatan_revisi }}</p>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $note->created_at->format('d M Y H:i') }}</div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada catatan revisi.</p>
    @endif

    <x-status-history :submission="$submission" />

<h2 class="mb-3 mt-6 text-lg font-semibold text-gray-800 dark:text-gray-200">Dosen Pembimbing</h2>
    @php
        $pembimbing = $submission->user->dosenPembimbingByUrutan ?? collect();
        $pembimbingData = $pembimbing->map(fn($d) => ['id'=>$d->id, 'name'=>$d->name, 'username'=>$d->username])->values()->toArray();
    @endphp
    <form method="POST" action="{{ route('admin.submissions.pembimbing.store', $submission) }}" class="max-w-3xl space-y-4">
        @csrf
        <div class="overflow-visible rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="p-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dosen Pembimbing (maks. 2)</label>
                <div x-data="searchableSelect({
                    endpoint: '/admin/schedules/search-users?type=dosen',
                    multiple: true,
                    initialSelected: @js($pembimbingData),
                    max: 2
                })" class="relative">
                    <div x-show="selected.length" class="mb-2 flex flex-wrap gap-1.5">
                        <template x-for="(item, index) in selected" :key="item.id">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <span class="rounded bg-brand-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-brand-600 dark:bg-brand-500/20 dark:text-brand-400" x-text="'Pembimbing ' + (index + 1)"></span>
                                <span x-text="item.name"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'(' + item.username + ')'"></span>
                                <button type="button" @click="remove(item)" class="text-gray-500 hover:text-error-600 dark:text-gray-400">×</button>
                            </span>
                        </template>
                    </div>

                    <input type="text" x-model="search" @input="debouncedFetch()" @focus="open = true" @keydown.escape="open = false; search = ''"
                           @keydown.arrow-down="open = true; moveHighlight('down')" @keydown.arrow-up="open = true; moveHighlight('up')"
                           @keydown.enter.prevent="selectHighlighted()" placeholder="Cari nama atau NIDN dosen..."
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">

                    <template x-for="item in selected" :key="item.id">
                        <input type="hidden" name="dosen_id[]" :value="item.id">
                    </template>

                    <div x-show="open" x-cloak @click.away="open = false"
                         class="absolute top-full left-0 right-0 z-[100] mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        <div x-show="loading" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Mencari...</div>
                        <template x-for="item in filteredResults()" :key="item.id">
                            <button type="button" @click="select(item)" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                                <span x-text="item.name"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'(' + item.username + ')'"></span>
                            </button>
                        </template>
                        <div x-show="!loading && !filteredResults().length" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil.</div>
                    </div>
                    <div x-show="maxedOut" class="mt-1 text-xs text-error-600 dark:text-error-500">Maksimal 2 dosen pembimbing.</div>
                </div>
                <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Klik hasil untuk menambahkan. Urutan otomatis mengikuti urutan pemilihan (Pembimbing I = pilihan pertama). Klik × untuk menghapus.</div>
                @error('dosen_id') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
        </div>
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
            Simpan Pembimbing
        </button>
    </form>

    @if($submission->assessmentForms->isNotEmpty())
    <h2 class="mb-3 mt-6 text-lg font-semibold text-gray-800 dark:text-gray-200">Penilaian Sidang</h2>
    <div class="space-y-4">
        @foreach($submission->assessmentForms as $form)
            @php
                $tipeLabel = $form->tipe_penilai === 'penguji' ? 'Penguji' : 'Pembimbing';
            @endphp
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <strong class="text-sm text-gray-800 dark:text-gray-200">{{ $form->dosen?->name ?? '-' }}</strong>
                    <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">{{ $tipeLabel }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Skor Akhir: <span class="font-semibold text-brand-600 dark:text-brand-400">{{ number_format($form->skor_total, 1) }}</span>
                </p>
                <a href="{{ route('admin.penilaian.cetak', [$submission, $form]) }}"
                   class="mt-3 inline-flex items-center justify-center gap-2 rounded-lg border border-brand-500 px-4 py-2 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7m-6 6v6m-4-4h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v10a2 2 0 002 2h6"></path>
                    </svg>
                    Cetak
                </a>
            </div>
        @endforeach
    </div>
    @endif
@endsection

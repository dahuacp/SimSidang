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
@endsection

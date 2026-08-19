@extends('layouts.app')

@section('title', 'Detail Submission')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Detail Submission</h1>
        <a href="{{ route('dosen.submissions.index') }}"
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
            <p class="text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-500 dark:text-gray-400">Diupload:</span> {{ $submission->created_at->format('d M Y H:i') }}
            </p>
        </div>
        @if($submission->file_path)
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('files.submission', $submission) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand-500 px-4 py-2 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
                    </svg>
                    Unduh Laporan
                </a>
                @include('dosen.submissions._ai-read-modal', ['submission' => $submission])
            </div>
        @endif
    </div>

    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Catatan Revisi</h2>
        <a href="{{ route('dosen.revision-notes.create', $submission) }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Catatan Revisi
        </a>
    </div>

    @if($submission->revisionNotes->isNotEmpty())
        <div class="space-y-3">
            @foreach($submission->revisionNotes as $note)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <strong class="text-sm text-gray-800 dark:text-gray-200">{{ $note->dosen->name ?? '-' }}</strong>
                            @if($note->dosen_id === auth()->id())
                                <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">Poin Anda</span>
                            @endif
                        </div>
                        @if($note->status_poin === 'open')
                            <span class="status-pill badge-open">Open</span>
                        @else
                            <span class="status-pill badge-resolved">Resolved</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $note->catatan_revisi }}</p>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $note->created_at->format('d M Y H:i') }}</div>

                    @if($note->attachments->isNotEmpty())
                        <div class="mt-3">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Balasan / Bukti:</span>
                            @foreach($note->attachments as $att)
                                <div class="mt-2">
                                    <a href="{{ route('files.attachment', $att) }}"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                        Unduh Lampiran
                                    </a>
                                    @if($att->keterangan_mahasiswa)
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $att->keterangan_mahasiswa }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($note->status_poin === 'open' && auth()->user()->can('resolve', $note))
                        <form method="POST" action="{{ route('dosen.revision-notes.resolve', $note) }}" class="mt-3 inline-block">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status_poin" value="resolved">
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-success-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-success-700"
                                    onclick="return confirm('Tandai poin ini sebagai resolved?')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Tandai Resolved
                            </button>
                        </form>
                    @elseif($note->status_poin === 'open')
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            Menunggu konfirmasi {{ $note->dosen->name ?? 'dosen terkait' }}.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada catatan revisi.</p>
    @endif

    <x-status-history :submission="$submission" />

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
                <a href="{{ route('dosen.penilaian.cetak', [$submission, $form]) }}"
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

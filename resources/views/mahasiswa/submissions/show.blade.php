@extends('layouts.app')

@section('title', 'Detail Submission')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Detail Submission</h1>
        <a href="{{ route('mahasiswa.submissions.index') }}"
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
                <span class="font-medium text-gray-500 dark:text-gray-400">Grup:</span> {{ $submission->schedule->nama_grup_sidang ?? '-' }}
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-500 dark:text-gray-400">Status:</span>
                @php
                    $label = match($submission->status) {
                        'pending' => ['Pending', 'badge-pending'],
                        'sidang_berjalan' => ['Sidang Berjalan', 'badge-open'],
                        'revisi' => ['Revisi', 'badge-open'],
                        'selesai' => ['Selesai', 'badge-resolved'],
                        default => [$submission->status, 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'],
                    };
                @endphp
                <span class="status-pill {{ $label[1] }}">{{ $label[0] }}</span>
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-500 dark:text-gray-400">Diupload:</span> {{ $submission->created_at->format('d M Y H:i') }}
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
        <a href="{{ route('mahasiswa.penilaian.show', $submission) }}"
           class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Lihat Penilaian
        </a>
    </div>

    <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-gray-200">Catatan Revisi</h2>

    @if($submission->revisionNotes->isNotEmpty())
        <div class="space-y-3">
            @foreach($submission->revisionNotes as $note)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2">
                        <strong class="text-sm text-gray-800 dark:text-gray-200">{{ $note->dosen->name ?? '-' }}</strong>
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
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $att->keterangan_mahasiswa }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($note->status_poin === 'open')
                        <form method="POST" action="{{ route('mahasiswa.revision-attachments.store', $note) }}" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Balas dengan bukti perbaikan</label>
                                <textarea name="keterangan_mahasiswa" rows="2" placeholder="Penjelasan perbaikan (opsional)"
                                          class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"></textarea>
                                <input type="file" name="file" accept=".pdf,.docx,.jpeg,.png"
                                       class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-500/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-brand-500/15 dark:file:text-brand-400">
                                <div class="text-xs text-gray-500 dark:text-gray-400">PDF/DOCX/JPEG/PNG, maksimal 5MB.</div>
                            </div>
                            <button type="submit"
                                    class="mt-3 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Kirim Bukti Perbaikan
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada catatan revisi.</p>
    @endif

    <x-status-history :submission="$submission" />
@endsection

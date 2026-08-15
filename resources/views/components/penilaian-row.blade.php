@props(['submission', 'tipe'])

@php
    $form = $submission->assessmentForms->first();
    $isi = $form ? $form->skor_total : null;
    $link = $form
        ? route('dosen.penilaian.edit', $form)
        : route('dosen.penilaian.create', ['submission' => $submission, 'tipe' => $tipe]);
    $label = $tipe === 'penguji' ? 'Penguji' : 'Pembimbing';
@endphp

<div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-900">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <strong class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $submission->user?->name ?? '-' }}</strong>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $submission->user?->username ?? '-' }}</span>
            <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">{{ $label }}</span>
        </div>
        <p class="mt-1 truncate text-sm text-gray-600 dark:text-gray-400">{{ $submission->judul_laporan }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ $submission->schedule?->nama_grup_sidang ?? '-' }} · {{ $submission->schedule?->jenisSidang?->nama ?? '-' }}
        </p>
    </div>
    <div class="flex shrink-0 items-center gap-3">
        @if($isi !== null)
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Skor: <span class="font-semibold text-brand-600 dark:text-brand-400">{{ number_format($isi, 1) }}</span>
            </span>
            <a href="{{ $link }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                Edit Penilaian
            </a>
        @else
            <span class="text-sm text-gray-500 dark:text-gray-400">Belum dinilai</span>
            <a href="{{ $link }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-brand-600">
                Isi Penilaian
            </a>
        @endif
    </div>
</div>
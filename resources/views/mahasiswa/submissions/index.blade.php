@extends('layouts.app')

@section('title', 'Laporan Saya — SISIDANG')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Laporan Saya</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar laporan yang telah Anda unggah.</p>
        </div>
        <a href="{{ route('mahasiswa.submissions.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
            </svg>
            Unggah Laporan
        </a>
    </div>

    @if($submissions->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
            </div>
            <p class="mb-3 text-gray-500 dark:text-gray-400">Belum ada laporan yang diunggah.</p>
            <a href="{{ route('mahasiswa.submissions.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
                Unggah Sekarang
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Judul Laporan</th>
                            <th class="px-4 py-3 font-medium">Grup Sidang</th>
                            <th class="px-4 py-3 font-medium">Ruangan</th>
                            <th class="px-4 py-3 font-medium">Tanggal Sidang</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($submissions as $submission)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $submission->judul_laporan }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $submission->schedule?->nama_grup_sidang ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $submission->schedule?->ruangan ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $submission->schedule?->tanggal_sidang?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3">
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
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('mahasiswa.submissions.show', $submission) }}"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

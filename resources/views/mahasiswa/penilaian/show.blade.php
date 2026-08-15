@extends('layouts.app')

@section('title', 'Penilaian — SISIDANG')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Penilaian Sidang</h1>
        <a href="{{ route('mahasiswa.submissions.show', $submission) }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    @if($submission->assessmentForms->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada penilaian dari dosen.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($submission->assessmentForms as $form)
                @php
                    $tipe = $form->tipe_penilai;
                    $tipeLabel = $tipe === 'penguji' ? 'Dosen Penguji' : 'Dosen Pembimbing';
                    $skorMap = collect($form->skor_per_item)->pluck('skor', 'item');
                @endphp
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800">
                        <div class="flex items-center gap-2">
                            <strong class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $form->dosen?->name ?? '-' }}</strong>
                            <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">{{ $tipeLabel }}</span>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            Skor Akhir: <span class="text-lg font-bold text-brand-600 dark:text-brand-400">{{ number_format($form->skor_total, 1) }}</span>
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <tr>
                                    <th class="px-5 py-3 font-medium">No</th>
                                    <th class="px-5 py-3 font-medium">Item Penilaian</th>
                                    <th class="px-5 py-3 font-medium text-right">Skor</th>
                                    <th class="px-5 py-3 font-medium text-right">Maks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($form->template?->items ?? [] as $idx => $item)
                                    <tr>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $idx + 1 }}</td>
                                        <td class="px-5 py-3 text-gray-800 dark:text-gray-200">{{ $item['name'] }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format((float) ($skorMap[$idx] ?? 0), 1) }}</td>
                                        <td class="px-5 py-3 text-right text-gray-500 dark:text-gray-400">{{ $item['maksimal'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($form->catatan)
                        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-800">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Catatan</p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $form->catatan }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
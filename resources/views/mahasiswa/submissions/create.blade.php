@extends('layouts.app')

@section('title', 'Unggah Laporan — SISIDANG')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Unggah Laporan</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PDF maksimal 10MB.</p>
        </div>
        <a href="{{ route('mahasiswa.submissions.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-error-500/20 bg-error-50 p-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <ul class="mb-0 list-disc space-y-1 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form method="POST" action="{{ route('mahasiswa.submissions.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($schedules->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                Anda belum di-plot ke grup sidang manapun. Hubungi admin untuk plotting jadwal sidang.
            </div>
        @else
            <div>
                <label for="schedule_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Grup Sidang</label>
                <select id="schedule_id" name="schedule_id" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    <option value="">Pilih grup sidang...</option>
                    @foreach($schedules as $schedule)
                        <option value="{{ $schedule->id }}" {{ old('schedule_id') == $schedule->id ? 'selected' : '' }}>
                            {{ $schedule->nama_grup_sidang }} — {{ $schedule->ruangan }} ({{ $schedule->tanggal_sidang->format('d M Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="judul_laporan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Judul Laporan</label>
            <input type="text" id="judul_laporan" name="judul_laporan" value="{{ old('judul_laporan') }}" required maxlength="255"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
        </div>
        <div>
            <label for="file" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">File Laporan (PDF)</label>
            <input type="file" id="file" name="file" accept="application/pdf" required
                   class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-500/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-600 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-brand-500/15 dark:file:text-brand-400 dark:focus:border-brand-800">
            <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Format: PDF, maksimal 10MB.</div>
        </div>
        @unless($schedules->isEmpty())
            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
                </svg>
                Unggah
            </button>
        @endunless
    </form>
    </div>
@endsection

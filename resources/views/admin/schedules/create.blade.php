@extends('layouts.app')

@section('title', 'Tambah Jadwal')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Tambah Jadwal Sidang</h1>

    <form method="POST" action="{{ route('admin.schedules.store') }}" class="max-w-3xl">
        @csrf
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Grup Sidang</label>
                <input type="text" name="nama_grup_sidang" value="{{ old('nama_grup_sidang') }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('nama_grup_sidang') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ruangan</label>
                <input type="text" name="ruangan" value="{{ old('ruangan') }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('ruangan') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tanggal Sidang</label>
                <input type="date" name="tanggal_sidang" value="{{ old('tanggal_sidang') }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('tanggal_sidang') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Mulai</label>
                    <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @error('jam_mulai') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Selesai</label>
                    <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @error('jam_selesai') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dosen (assign ke jadwal ini)</label>
            <select name="dosens[]" multiple class="min-h-30 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @foreach($dosens as $dosen)
                    <option value="{{ $dosen->id }}" {{ in_array($dosen->id, old('dosens', [])) ? 'selected' : '' }}>{{ $dosen->name }} ({{ $dosen->username }})</option>
                @endforeach
            </select>
            <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Ctrl+klik untuk pilih banyak.</div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
                Simpan
            </button>
            <a href="{{ route('admin.schedules.index') }}"
               class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:text-brand-500 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>
@endsection

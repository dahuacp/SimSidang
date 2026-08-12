@extends('layouts.app')

@section('title', 'Tambah Revisi — SISIDANG')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Tambah Catatan Revisi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $submission->user?->name }} ({{ $submission->user?->username }}) — {{ $submission->judul_laporan }}</p>
        </div>
        <a href="{{ route('dosen.submissions.show', $submission) }}"
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
        <form method="POST" action="{{ route('dosen.revision-notes.store', $submission) }}" class="space-y-5">
            @csrf
            <div>
                <label for="catatan_revisi" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Catatan Revisi</label>
                <textarea id="catatan_revisi" name="catatan_revisi" rows="5" required
                          placeholder="Tuliskan poin revisi untuk mahasiswa..."
                          class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"></textarea>
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Simpan Poin Revisi
            </button>
        </form>
    </div>
@endsection

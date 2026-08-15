@extends('layouts.app')

@section('title', 'Edit Penilaian — SISIDANG')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Edit Penilaian</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $assessmentForm->submission->user?->name }} ({{ $assessmentForm->submission->user?->username }}) — {{ $assessmentForm->submission->judul_laporan }}
            </p>
        </div>
        <a href="{{ route('dosen.penilaian.index') }}"
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

    @include('dosen.penilaian._form', [
        'submission' => $assessmentForm->submission,
        'template' => $assessmentForm->template,
        'tipe' => $assessmentForm->tipe_penilai,
        'assessmentForm' => $assessmentForm,
    ])
@endsection
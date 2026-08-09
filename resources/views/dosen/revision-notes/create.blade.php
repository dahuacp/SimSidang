@extends('layouts.app')

@section('title', 'Tambah Revisi — SISIDANG')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Tambah Catatan Revisi</h1>
            <p class="text-muted small mb-0">{{ $submission->user?->name }} ({{ $submission->user?->username }}) — {{ $submission->judul_laporan }}</p>
        </div>
        <a href="{{ route('dosen.submissions.show', $submission) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="max-width: 640px;">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('dosen.revision-notes.store', $submission) }}">
                @csrf
                <div class="mb-4">
                    <label for="catatan_revisi" class="form-label">Catatan Revisi</label>
                    <textarea class="form-control" id="catatan_revisi" name="catatan_revisi" rows="5"
                              placeholder="Tuliskan poin revisi untuk mahasiswa..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Poin Revisi
                </button>
            </form>
        </div>
    </div>
@endsection

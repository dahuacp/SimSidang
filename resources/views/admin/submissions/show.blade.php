@extends('layouts.app')

@section('title', 'Detail Submission')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Detail Submission</h4>
        <a href="{{ route('admin.submissions.index') }}" class="btn btn-link btn-sm">Kembali</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>{{ $submission->judul_laporan ?: '(Belum ada judul)' }}</h5>
            <p class="mb-1"><small class="text-muted">Mahasiswa:</small> {{ $submission->user->name }} ({{ $submission->user->username }})</p>
            <p class="mb-1"><small class="text-muted">Grup:</small> {{ $submission->schedule->nama_grup_sidang ?? '-' }}</p>
            <p class="mb-1"><small class="text-muted">Status:</small>
                <span class="badge bg-{{ $submission->status === 'selesai' ? 'success' : ($submission->status === 'revisi' ? 'warning' : 'secondary') }}">{{ ucfirst($submission->status) }}</span>
            </p>
            @if($submission->file_path)
                <a href="{{ route('files.submission', $submission) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> Unduh Laporan
                </a>
            @endif
        </div>
    </div>

    <h5 class="mb-2">Catatan Revisi</h5>
    @if($submission->revisionNotes->isNotEmpty())
        <div class="list-group mb-3">
            @foreach($submission->revisionNotes as $note)
                <div class="list-group-item">
                    <strong>{{ $note->dosen->name ?? '-' }}</strong> <span class="badge bg-{{ $note->status_poin === 'open' ? 'warning text-dark' : 'success' }}">{{ ucfirst($note->status_poin) }}</span>
                    <p class="mb-1 mt-1">{{ $note->catatan_revisi }}</p>
                    <small class="text-muted">{{ $note->created_at->format('d M Y H:i') }}</small>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-muted">Belum ada catatan revisi.</p>
    @endif

    <x-status-history :submission="$submission" />
</div>
@endsection

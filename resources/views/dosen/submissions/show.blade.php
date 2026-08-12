@extends('layouts.app')

@section('title', 'Detail Submission')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Detail Submission</h4>
        <a href="{{ route('dosen.submissions.index') }}" class="btn btn-link btn-sm">Kembali</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>{{ $submission->judul_laporan ?: '(Belum ada judul)' }}</h5>
            <p class="mb-1"><small class="text-muted">Mahasiswa:</small> {{ $submission->user->name }} ({{ $submission->user->username }})</p>
            <p class="mb-1"><small class="text-muted">Grup:</small> {{ $submission->schedule->nama_grup_sidang ?? '-' }}</p>
            <p class="mb-1"><small class="text-muted">Status:</small>
                <span class="badge bg-{{ $submission->status === 'selesai' ? 'success' : ($submission->status === 'revisi' ? 'warning' : 'secondary') }}">{{ ucfirst($submission->status) }}</span>
            </p>
            <p class="mb-2"><small class="text-muted">Diupload:</small> {{ $submission->created_at->format('d M Y H:i') }}</p>
            @if($submission->file_path)
                <a href="{{ route('files.submission', $submission) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> Unduh Laporan
                </a>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Catatan Revisi</h5>
        <a href="{{ route('dosen.revision-notes.create', $submission) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Catatan Revisi
        </a>
    </div>

    @if($submission->revisionNotes->isNotEmpty())
        <div class="list-group mb-3">
            @foreach($submission->revisionNotes as $note)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $note->dosen->name ?? '-' }}</strong>
                        @if($note->status_poin === 'open')
                            <span class="status-pill badge-open">Open</span>
                        @else
                            <span class="status-pill badge-resolved">Resolved</span>
                        @endif
                    </div>
                    <p class="mb-1 mt-1">{{ $note->catatan_revisi }}</p>
                    <small class="text-muted">{{ $note->created_at->format('d M Y H:i') }}</small>

                    @if($note->attachments->isNotEmpty())
                        <div class="mt-2">
                            <small class="fw-semibold">Balasan / Bukti:</small>
                            @foreach($note->attachments as $att)
                                <div class="mt-1">
                                    <a href="{{ route('files.attachment', $att) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-paperclip"></i> Unduh Lampiran
                                    </a>
                                    @if($att->keterangan_mahasiswa)
                                        <small class="text-muted d-block">{{ $att->keterangan_mahasiswa }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($note->status_poin === 'open')
                        <form method="POST" action="{{ route('dosen.revision-notes.resolve', $note) }}" class="d-inline mt-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status_poin" value="resolved">
                            <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirm('Tandai poin ini sebagai resolved?')">
                                <i class="bi bi-check-lg"></i> Tandai Resolved
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-muted">Belum ada catatan revisi.</p>
    @endif

    <x-status-history :submission="$submission" />
</div>
@endsection

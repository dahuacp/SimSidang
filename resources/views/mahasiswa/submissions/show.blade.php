@extends('layouts.app')

@section('title', 'Detail Submission')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Detail Submission</h4>
        <a href="{{ route('mahasiswa.submissions.index') }}" class="btn btn-link btn-sm">Kembali</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>{{ $submission->judul_laporan ?: '(Belum ada judul)' }}</h5>
            <p class="mb-1"><small class="text-muted">Grup:</small> {{ $submission->schedule->nama_grup_sidang ?? '-' }}</p>
            <p class="mb-1"><small class="text-muted">Status:</small>
                <span class="badge bg-{{ $submission->status === 'selesai' ? 'success' : ($submission->status === 'revisi' ? 'warning' : 'secondary') }}">{{ ucfirst($submission->status) }}</span>
            </p>
            <p class="mb-1"><small class="text-muted">Diupload:</small> {{ $submission->created_at->format('d M Y H:i') }}</p>
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
                    <div class="d-flex justify-content-between">
                        <strong>{{ $note->dosen->name ?? '-' }}</strong>
                        @if($note->status_poin === 'open')
                            <span class="badge bg-warning text-dark">Open</span>
                        @else
                            <span class="badge bg-success">Resolved</span>
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
                                        <p class="mb-0 small text-muted mt-1">{{ $att->keterangan_mahasiswa }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($note->status_poin === 'open')
                        <form method="POST" action="{{ route('mahasiswa.revision-attachments.store', $note) }}" enctype="multipart/form-data" class="mt-3">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Balas dengan bukti perbaikan</label>
                                <textarea class="form-control mb-2" name="keterangan_mahasiswa" rows="2"
                                          placeholder="Penjelasan perbaikan (opsional)"></textarea>
                                <input type="file" class="form-control" name="file"
                                       accept=".pdf,.docx,.jpeg,.png">
                                <div class="form-text">PDF/DOCX/JPEG/PNG, maksimal 5MB.</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-send"></i> Kirim Bukti Perbaikan
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

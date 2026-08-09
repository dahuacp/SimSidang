@extends('layouts.app')

@section('title', 'Laporan Saya — SISIDANG')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Laporan Saya</h1>
            <p class="text-muted small mb-0">Daftar laporan yang telah Anda unggah.</p>
        </div>
        <a href="{{ route('mahasiswa.submissions.create') }}" class="btn btn-primary">
            <i class="bi bi-upload"></i> Unggah Laporan
        </a>
    </div>

    @if($submissions->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mb-3 mt-2">Belum ada laporan yang diunggah.</p>
                <a href="{{ route('mahasiswa.submissions.create') }}" class="btn btn-primary">Unggah Sekarang</a>
            </div>
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Judul Laporan</th>
                            <th>Grup Sidang</th>
                            <th>Ruangan</th>
                            <th>Tanggal Sidang</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                            <tr>
                                <td class="fw-semibold">{{ $submission->judul_laporan }}</td>
                                <td>{{ $submission->schedule?->nama_grup_sidang ?? '-' }}</td>
                                <td>{{ $submission->schedule?->ruangan ?? '-' }}</td>
                                <td>{{ $submission->schedule?->tanggal_sidang?->format('d M Y') ?? '-' }}</td>
                                <td>
                                    @php
                                        $label = match($submission->status) {
                                            'pending' => ['Pending', 'badge-pending'],
                                            'sidang_berjalan' => ['Sidang Berjalan', 'badge-open'],
                                            'revisi' => ['Revisi', 'badge-open'],
                                            'selesai' => ['Selesai', 'badge-resolved'],
                                            default => [$submission->status, 'bg-secondary'],
                                        };
                                    @endphp
                                    <span class="status-pill {{ $label[1] }}">{{ $label[0] }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('mahasiswa.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
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

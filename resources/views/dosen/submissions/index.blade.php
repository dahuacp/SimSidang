@extends('layouts.app')

@section('title', 'Jadwal Sidang Hari Ini — SISIDANG')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1">Jadwal Sidang Hari Ini</h1>
        <p class="text-muted small mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    @if($schedules->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <p class="text-muted mb-0 mt-2">Tidak ada jadwal sidang hari ini.</p>
            </div>
        </div>
    @else
        <div class="d-flex flex-column gap-4">
            @foreach($schedules as $schedule)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold">{{ $schedule->nama_grup_sidang }}</span>
                            <span class="text-muted ms-2"><i class="bi bi-geo-alt"></i> {{ $schedule->ruangan }}</span>
                        </div>
                        <span class="small text-muted">{{ \Carbon\Carbon::parse($schedule->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->jam_selesai)->format('H:i') }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Mahasiswa</th>
                                    <th>NIM</th>
                                    <th>Judul Laporan</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedule->submissions as $submission)
                                    <tr>
                                        <td class="fw-semibold">{{ $submission->user?->name ?? '-' }}</td>
                                        <td>{{ $submission->user?->username ?? '-' }}</td>
                                        <td>{{ $submission->judul_laporan ?? '-' }}</td>
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
                                            <a href="{{ route('dosen.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada mahasiswa terdaftar di grup ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

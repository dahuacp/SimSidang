@extends('layouts.app')

@section('title', 'Dashboard Admin — SISIDANG')

@section('content')
    <h1 class="h4 mb-4">Dashboard Admin</h1>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-people fs-2 text-primary"></i>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['mahasiswa'] }}</div>
                        <div class="text-muted small">Mahasiswa</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-person-video3 fs-2 text-primary"></i>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['dosen'] }}</div>
                        <div class="text-muted small">Dosen Penguji</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-text fs-2 text-primary"></i>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['submissions'] }}</div>
                        <div class="text-muted small">Total Laporan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-circle fs-2 text-warning"></i>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['revisi_terbuka'] }}</div>
                        <div class="text-muted small">Revisi Terbuka</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Jadwal Sidang</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Grup Sidang</th>
                        <th>Ruangan</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th class="text-end">Jumlah Laporan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                        <tr>
                            <td class="fw-semibold">{{ $schedule->nama_grup_sidang }}</td>
                            <td>{{ $schedule->ruangan }}</td>
                            <td>{{ $schedule->tanggal_sidang->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($schedule->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->jam_selesai)->format('H:i') }}</td>
                            <td class="text-end">{{ $schedule->submissions_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada jadwal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

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

    {{-- Chart Row 1 --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Submission</h5>
                </div>
                <div class="card-body">
                    <div id="chart-status-submission"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Submission per Jadwal</h5>
                </div>
                <div class="card-body">
                    <div id="chart-schedule-submissions"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart Row 2 --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revisi Open vs Resolved</h5>
                </div>
                <div class="card-body">
                    <div id="chart-revision-stats"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tren Status per Hari</h5>
                </div>
                <div class="card-body">
                    <div id="chart-status-trend"></div>
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

@push('scripts')
<script type="module">
import ApexCharts from '../apex.js';

// Chart 1: Status Submission (Donut)
const statusData = @json($submissionStatus);
const statusLabels = Object.keys(statusData);
const statusValues = Object.values(statusData);
new ApexCharts(document.querySelector('#chart-status-submission'), {
    chart: { type: 'donut', height: 300 },
    labels: statusLabels,
    series: statusValues,
    colors: ['#ffc107', '#17a2b8', '#0d6efd', '#28a745', '#dc3545', '#6f42c1'],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true, formatter: (val) => val + '%' }
}).render();

// Chart 2: Submission per Jadwal (Bar)
const scheduleData = @json($scheduleSubmissions);
const scheduleNames = scheduleData.map(s => s.nama_grup_sidang);
const scheduleCounts = scheduleData.map(s => s.submissions_count);
new ApexCharts(document.querySelector('#chart-schedule-submissions'), {
    chart: { type: 'bar', height: 300 },
    series: [{ name: 'Submission', data: scheduleCounts }],
    xaxis: { categories: scheduleNames },
    colors: ['#F5B400'],
    legend: { show: false },
    plotOptions: {
        bar: { borderRadius: 4, distributed: true }
    }
}).render();

// Chart 3: Revisi Open vs Resolved (Donut)
const revisionData = @json($revisionStats);
new ApexCharts(document.querySelector('#chart-revision-stats'), {
    chart: { type: 'donut', height: 300 },
    labels: ['Open', 'Resolved'],
    series: [revisionData['open'] ?? 0, revisionData['resolved'] ?? 0],
    colors: ['#dc3545', '#28a745'],
    legend: { position: 'bottom' }
}).render();

// Chart 4: Tren Status per Hari (Area)
const trendData = @json($statusTrend);
const dates = Object.keys(trendData);
const statusMap = {
    pending: { label: 'Pending', color: '#ffc107' },
    sidang_berjalan: { label: 'Sidang Berjalan', color: '#17a2b8' },
    revisi: { label: 'Revisi', color: '#0d6efd' },
    selesai: { label: 'Selesai', color: '#28a745' }
};
const series = Object.entries(statusMap).map(([key, cfg]) => ({
    name: cfg.label,
    data: dates.map(d => trendData[d]?.[key] || 0)
}));
const seriesColors = Object.values(statusMap).map(s => s.color);

new ApexCharts(document.querySelector('#chart-status-trend'), {
    chart: { type: 'area', height: 300 },
    series: series,
    xaxis: { categories: dates },
    colors: seriesColors,
    stroke: { curve: 'smooth' },
    legend: { position: 'bottom' }
}).render();
</script>
@endpush
@endsection

@extends('layouts.app')

@section('title', 'Dashboard Admin — SISIDANG')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Dashboard Admin</h1>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $stats['mahasiswa'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Mahasiswa</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $stats['dosen'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Dosen Penguji</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 0l2-2m-2 2l-2 2m2 6H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $stats['submissions'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Laporan</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-100 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $stats['revisi_terbuka'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Revisi Terbuka</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart Row 1 --}}
    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">Status Submission</h2>
            </div>
            <div class="p-5">
                <div id="chart-status-submission"></div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">Submission per Jadwal</h2>
            </div>
            <div class="p-5">
                <div id="chart-schedule-submissions"></div>
            </div>
        </div>
    </div>

    {{-- Chart Row 2 --}}
    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">Revisi Open vs Resolved</h2>
            </div>
            <div class="p-5">
                <div id="chart-revision-stats"></div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">Tren Status per Hari</h2>
            </div>
            <div class="p-5">
                <div id="chart-status-trend"></div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">Jadwal Sidang</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Grup Sidang</th>
                        <th class="px-4 py-3 font-medium">Ruangan</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Jam</th>
                        <th class="px-4 py-3 font-medium text-right">Jumlah Laporan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($schedules as $schedule)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $schedule->nama_grup_sidang }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $schedule->ruangan }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $schedule->tanggal_sidang->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($schedule->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->jam_selesai)->format('H:i') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">{{ $schedule->submissions_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada jadwal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@push('scripts')
<script type="module">
    const ApexCharts = window.ApexCharts;

    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    // Chart 1: Status Submission (Donut)
    const statusData = @json($submissionStatus);
    const statusLabels = Object.keys(statusData);
    const statusValues = Object.values(statusData);
    new ApexCharts(document.querySelector('#chart-status-submission'), {
        chart: { type: 'donut', height: 300 },
        labels: statusLabels,
        series: statusValues,
        colors: [cssVar('--chart-pending'), cssVar('--chart-sidang-berjalan'), cssVar('--chart-revisi'), cssVar('--chart-selesai'), cssVar('--chart-open'), '#6f42c1'],
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
        colors: [cssVar('--chart-pending')],
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
        colors: [cssVar('--chart-open'), cssVar('--chart-resolved')],
        legend: { position: 'bottom' }
    }).render();

    // Chart 4: Tren Status per Hari (Area)
    const trendData = @json($statusTrend);
    const dates = Object.keys(trendData);
    const statusMap = {
        pending: { label: 'Pending', color: cssVar('--chart-pending') },
        sidang_berjalan: { label: 'Sidang Berjalan', color: cssVar('--chart-sidang-berjalan') },
        revisi: { label: 'Revisi', color: cssVar('--chart-revisi') },
        selesai: { label: 'Selesai', color: cssVar('--chart-selesai') }
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

@extends('layouts.app')

@section('title', 'Rekap Hasil Penilaian')

@section('content')
<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Rekap Hasil Penilaian</h1>
    <div class="flex flex-wrap gap-2">
        <form method="GET" class="flex gap-2" role="search">
            <select name="prodi_id" class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
                <option value="">Semua Prodi</option>
                @foreach($prodis as $prodi)
                <option value="{{ $prodi->id }}" {{ request('prodi_id') == $prodi->id ? 'selected' : '' }}>{{ $prodi->nama_prodi }}</option>
                @endforeach
            </select>
            <select name="sort" class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
                <option value="desc" {{ request('sort') == 'asc' ? '' : 'selected' }}>Nilai Tertinggi</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Nilai Terendah</option>
            </select>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filter</button>
        </form>
        <a href="{{ route('admin.rekap.nilai-excel', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-success-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
            </svg>
            Export Excel
        </a>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 dark:border-gray-800 dark:bg-gray-800">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">Distribusi Nilai</h3>
        </div>
        <div class="p-4">
            <div id="chart-distribution" class="w-full" style="height: 200px;"></div>
        </div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 dark:border-gray-800 dark:bg-gray-800">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">Rata-Rata Nilai per Prodi</h3>
        </div>
        <div class="p-4">
            <div id="chart-prodi" class="w-full" style="height: 200px;"></div>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">No</th>
                    <th class="px-4 py-3">Mahasiswa</th>
                    <th class="px-4 py-3">NIM</th>
                    <th class="px-4 py-3">Progdi</th>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Dospem</th>
                    <th class="px-4 py-3">Penguji</th>
                    <th class="px-4 py-3 font-semibold">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($rows as $row)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-4 py-3">{{ $row['no'] }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{{ $row['mahasiswa'] }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row['nim'] }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['prodi'] }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['judul'] }}</td>
                    <td class="px-4 py-3">{{ $row['dospem_nilai'] !== '-' ? number_format($row['dospem_nilai'], 1) : '—' }}</td>
                    <td class="px-4 py-3">{{ $row['penguji_nilai'] !== '-' ? number_format($row['penguji_nilai'], 1) : '—' }}</td>
                    <td class="px-4 py-3 font-semibold"><span class="badge-status">{{ $row['nilai_akhir'] }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data penilaian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ApexCharts = window.ApexCharts;

    // Chart 1: Distribusi Nilai (Donut)
    const distData = @json($chartData['distribution']);
    const distLabels = ['A (>=80)', 'B (70-79)', 'C (60-69)', 'D (50-59)', 'E (<50)'];
    const distColors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#6B7280'];

    new ApexCharts(document.querySelector('#chart-distribution'), {
        chart: { type: 'donut', height: 200 },
        labels: distLabels,
        series: [distData['A'], distData['B'], distData['C'], distData['D'], distData['E']],
        colors: distColors,
        legend: { position: 'bottom' }
    }).render();

    // Chart 2: Rata-Rata Nilai per Prodi (Bar)
    const prodiData = @json($chartData['perProdi']);
    const prodiNames = prodiData.map(p => p.nama);
    const prodiAvg = prodiData.map(p => p.rata_rata);

    new ApexCharts(document.querySelector('#chart-prodi'), {
        chart: { type: 'bar', height: 200, toolbar: { show: false } },
        series: [{ name: 'Rata-Rata Nilai', data: prodiAvg }],
        xaxis: { categories: prodiNames },
        yaxis: { min: 0, max: 100 },
        colors: [getComputedStyle(document.documentElement).getPropertyValue('--brand-500').trim()],
        plotOptions: { bar: { borderRadius: 4 } },
        dataLabels: { enabled: false }
    }).render();
</script>
@endpush
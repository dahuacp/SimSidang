@extends('layouts.app')

@section('title', 'Rekap Submission')

@section('content')
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Rekap Status Submission</h1>
        <div class="flex flex-wrap gap-2">
            <form method="GET" class="flex gap-2" role="search">
                <input type="search" name="search" placeholder="Cari..." value="{{ request('search') }}" aria-label="Cari"
                       class="h-10 w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
            </form>
            <a href="{{ route('admin.rekap.export-excel', request()->query()) }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-success-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
                </svg>
                Export Excel
            </a>
            <a href="{{ route('admin.rekap.export-pdf', request()->query()) }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-error-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
                </svg>
                Export PDF
            </a>
        </div>
    </div>

    @php $submissions = \App\Models\Submission::with(['user','schedule'])->when(request('search'), fn($q,$s) => $q->where('judul_laporan','like',"%$s%")->orWhereHas('user', fn($q2) => $q2->where('name','like',"%$s%")->orWhere('username','like',"%$s%")))->orderBy('created_at','desc')->paginate(15)->withQueryString() @endphp

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">Mahasiswa</th>
                        <th class="px-4 py-3 font-medium">Grup Sidang</th>
                        <th class="px-4 py-3 font-medium">Judul</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Poin Revisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($submissions as $s)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $loop->iteration + ($submissions->currentPage()-1)*$submissions->perPage() }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $s->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->user->username }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $s->schedule->nama_grup_sidang ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $s->judul_laporan ?? '-' }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$s->status" /></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $s->revisionNotes ? \App\Models\RevisionNote::where('submission_id',$s->id)->where('status_poin','open')->count() : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada submission.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
@endsection

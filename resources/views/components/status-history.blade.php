@props(['submission'])

<div class="mt-4">
    <h6 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Riwayat Perubahan Status</h6>
    @if($submission->statusLogs->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-gray-800 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2 font-medium">Tanggal</th>
                        <th class="px-3 py-2 font-medium">Dari</th>
                        <th class="px-3 py-2 font-medium">Ke</th>
                        <th class="px-3 py-2 font-medium">Diubah oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($submission->statusLogs as $log)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2"><span class="status-pill {{ $log->status_lama === 'selesai' ? 'badge-resolved' : 'badge-pending' }}">{{ ucfirst(str_replace('_', ' ', $log->status_lama)) }}</span></td>
                            <td class="px-3 py-2"><span class="status-pill {{ $log->status_baru === 'selesai' ? 'badge-resolved' : 'badge-pending' }}">{{ ucfirst(str_replace('_', ' ', $log->status_baru)) }}</span></td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">{{ optional($log->diubahOleh)->name ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Belum ada riwayat perubahan status.</p>
    @endif
</div>

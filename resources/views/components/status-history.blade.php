@props(['submission'])

<div class="mt-4">
    <h6 class="mb-2">Riwayat Perubahan Status</h6>
    @if($submission->statusLogs->isNotEmpty())
        <table class="table table-sm table-borderless">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Dari</th>
                    <th>Ke</th>
                    <th>Diubah oleh</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submission->statusLogs as $log)
                    <tr>
                        <td><small class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</small></td>
                        <td><span class="badge bg-secondary">{{ ucfirst($log->status_lama) }}</span></td>
                        <td><span class="badge bg-success">{{ ucfirst($log->status_baru) }}</span></td>
                        <td><small>{{ optional($log->diubahOleh)->name ?: '-' }}</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted small mb-0">Belum ada riwayat perubahan status.</p>
    @endif
</div>

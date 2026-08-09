<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h4 { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; }
        th { background: #f5f5f5; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; }
        .success { background: #d4edda; }
        .warning { background: #fff3cd; }
        .secondary { background: #e2e3e5; }
    </style>
</head>
<body>
    <h4>Rekap Status Submission — SISIDANG</h4>
    <small>Dicetak: {{ now()->format('d M Y H:i') }}</small>
    @if(request('search'))<small class="ms-2">Filter: "{{ request('search') }}"</small>@endif
    <table>
        <thead>
            <tr>
                <th>#</th><th>Mahasiswa</th><th>NIM</th><th>Grup Sidang</th><th>Judul</th><th>Status</th><th>Poin Open</th><th>Resolved</th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $s)
                @php $open = $s->revisionNotes->where('status_poin','open')->count(); $resolved = $s->revisionNotes->where('status_poin','resolved')->count(); @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $s->user->name }}</td>
                    <td>{{ $s->user->username }}</td>
                    <td>{{ $s->schedule->nama_grup_sidang ?? '-' }}</td>
                    <td>{{ $s->judul_laporan ?? '-' }}</td>
                    <td>{{ ucfirst($s->status) }}</td>
                    <td>{{ $open }}</td>
                    <td>{{ $resolved }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

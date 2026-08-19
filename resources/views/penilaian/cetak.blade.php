<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penilaian Sidang — {{ $assessmentForm->submission->user->username }}</title>
    <style>
        * {
            font-family: sans-serif;
            font-size: 10pt;
            line-height: 1.4;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .header-table td {
            border: none;
            padding: 2px 4px;
        }
        .score-cell {
            text-align: center;
            width: 24px;
        }
        .score-selected {
            background-color: #6366f1;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body style="margin: 20px;">

@php
    $fakultas = $assessmentForm->submission->user->prodi?->fakultas;
    $prodiList = $fakultas ? \App\Models\Prodi::where('fakultas_id', $fakultas->id)->pluck('nama_prodi')->toArray() : [];
    $tipeLabel = $assessmentForm->tipe_penilai === 'penguji' ? 'Dosen Penguji' : 'Dosen Pembimbing';
    $tipeLabelShort = $assessmentForm->tipe_penilai === 'penguji' ? 'Penguji' : 'Pembimbing';
@endphp

<table class="header-table" style="margin-bottom: 16px;">
    <tr>
        <td class="bold">{{ $university['name'] ?? config('university.name') }}</td>
    </tr>
    <tr>
        <td>{{ $fakultas?->nama_fakultas ?? '' }}</td>
    </tr>
    <tr>
        <td>PRODI: {{ implode(', ', $prodiList) }}</td>
    </tr>
    <tr>
        <td>Alamat : {{ $university['address'] ?? '' }} Telp.{{ $university['phone'] ?? '' }} Fax. {{ $university['fax'] ?? '' }}</td>
    </tr>
    <tr>
        <td>Website: {{ $university['website'] ?? '' }}, Email: {{ $university['email'] ?? '' }}</td>
    </tr>
</table>

<table style="margin-bottom: 20px;">
    <tr>
        <td class="bold">EVALUASI PENILAIAN SIDANG</td>
        <td>({{ $tipeLabel }})</td>
    </tr>
</table>

<table style="margin-bottom: 20px;">
    <tr>
        <td class="bold">Nama Mahasiswa</td>
        <td>: {{ $assessmentForm->submission->user->name }}</td>
    </tr>
    <tr>
        <td class="bold">Nomor Induk Mahasiswa</td>
        <td>: {{ $assessmentForm->submission->user->username }}</td>
    </tr>
    <tr>
        <td class="bold">Program Studi</td>
        <td>: {{ $assessmentForm->submission->user->prodi?->nama_prodi ?? '-' }}</td>
    </tr>
    <tr>
        <td class="bold">Judul Tugas Akhir</td>
        <td>: {{ $assessmentForm->submission->judul_laporan ?: '-' }}</td>
    </tr>
    @if(isset($dospem[0]))
    <tr>
        <td class="bold">Dosen Pembimbing I</td>
        <td>: {{ $dospem[0]->name }}{{ $dospem[0]->title ? ', '.$dospem[0]->title : '' }}</td>
    </tr>
    @endif
    @if(isset($dospem[1]))
    <tr>
        <td class="bold">Dosen Pembimbing II</td>
        <td>: {{ $dospem[1]->name }}{{ $dospem[1]->title ? ', '.$dospem[1]->title : '' }}</td>
    </tr>
    @endif
</table>

<table>
    <thead>
        <tr>
            <th class="center">#</th>
            <th>SASARAN PENILAIAN</th>
            <th>NILAI</th>
        </tr>
    </thead>
    <tbody>
        @php
            $skorMap = collect($assessmentForm->skor_per_item)->pluck('skor', 'item');
        @endphp
        @foreach($assessmentForm->template?->items ?? [] as $idx => $item)
            @php
                $maksimal = $item['maksimal'];
                $step = $maksimal / 5;
                $options = [$step, 2*$step, 3*$step, 4*$step, $maksimal];
                $selectedSkor = $skorMap[$idx] ?? null;
            @endphp
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{{ $item['name'] }}</td>
                <td>
                    @foreach($options as $opt)
                        @php
                            $isInt = $opt == floor($opt);
                            $displayVal = $isInt ? (int)$opt : $opt;
                        @endphp
                        @if($selectedSkor == $displayVal)
                            <span class="score-cell score-selected">{{ $displayVal }}</span>
                        @else
                            <span class="score-cell">{{ $displayVal }}</span>
                        @endif
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="margin-top: 16px;">
    <tr>
        <td class="bold">NILAI PENILAIAN SIDANG = Σ nilai / {{ $assessmentForm->template->nilai_penyebut }} × {{ $assessmentForm->template->nilai_pengali }}</td>
        <td class="bold center" style="width: 100px;">{{ $assessmentForm->skor_total }}</td>
    </tr>
</table>

@if($assessmentForm->catatan)
<table style="margin-top: 10px;">
    <tr>
        <td class="bold">CATATAN :</td>
        <td>{{ $assessmentForm->catatan }}</td>
    </tr>
</table>
@endif

<table style="margin-top: 24px;">
    <tr>
        <td>{!! \Carbon\Carbon::now()->format('d M Y') !!},</td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td>{{ $assessmentForm->dosen->name }}{{ $assessmentForm->dosen->title ? ', '.$assessmentForm->dosen->title : '' }}</td>
    </tr>
    <tr>
        <td></td>
        <td class="bold">{{ $tipeLabel }}</td>
    </tr>
</table>

@unless($assessmentForm->catatan)
<br><br><br>
@endunless

<p class="center" style="margin-top: 32px; font-size: 9pt; color: #666;">
    *) Harap lingkari nilai angka yang diberikan
</p>

</body>
</html>

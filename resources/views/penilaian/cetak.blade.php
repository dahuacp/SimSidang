<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penilaian Sidang — {{ $assessmentForm->submission->user->username }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
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
            padding: 2px 0;
        }
        .logo-cell {
            text-align: center;
            vertical-align: middle;
            width: 100px;
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
        .sig-table td {
            border: none;
        }
        .plain-table td, .plain-table th {
            border: none;
        }
    </style>
</head>
<body style="margin: 20px;">

@inject('qrService', 'App\Services\QrCodeService')

@php
    $isDospem = $assessmentForm->tipe_penilai === 'dospem';
    $fakultas = $assessmentForm->submission->user->prodi?->fakultas;
    $prodiList = $fakultas ? \App\Models\Prodi::where('fakultas_id', $fakultas->id)->pluck('nama_prodi')->toArray() : [];
    $logoPath = public_path('storage/docs/LOGO.png');
    $penilai = $dospem->firstWhere('id', $assessmentForm->dosen_id);
    $urutan = $penilai?->pivot?->urutan;
    $tipeLabelDetail = $isDospem
        ? 'Dosen Pembimbing '.($urutan === 2 ? 'II' : ($urutan === 1 ? 'I' : ''))
        : 'Dosen Penguji';
    $judulUtama = $isDospem ? 'EVALUASI BIMBINGAN TUGAS AKHIR' : 'EVALUASI PENILAIAN SIDANG';
    $labelNilai = $isDospem ? 'NILAI BIMBINGAN TUGAS AKHIR' : 'NILAI PENILAIAN SIDANG';
    $tanggal = $assessmentForm->created_at->locale('id')->translatedFormat('d F Y');
@endphp

<table class="header-table" style="margin-bottom: 16px;">
    <tr>
        <td></td>
        <td class="bold" style="font-size: 14pt; padding-left: 10px;">{{ $university['name'] ?? config('university.name') }}</td>
    </tr>
    <tr>
        <td class="logo-cell"></td>
        <td></td>
    </tr>
    <tr>
        <td class="logo-cell" rowspan="4"><img src="{{ $logoPath }}" alt="Logo" style="max-width: 100px; height: auto;"></td>
        <td class="bold" style="padding-left: 10px;">{{ $fakultas?->nama_fakultas ?? '' }}</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">PRODI: {{ implode(', ', $prodiList) }}</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">Alamat: {{ $university['address'] ?? '' }} Telp.{{ $university['phone'] ?? '' }} Fax. {{ $university['fax'] ?? '' }}</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">Website: {{ $university['website'] ?? '' }}, Email: {{ $university['email'] ?? '' }}</td>
    </tr>
</table>

<table class="plain-table" style="margin-bottom: 20px;">
    <tr>
        <td class="bold">{{ $judulUtama }}</td>
        <td>({{ $tipeLabelDetail }})</td>
    </tr>
</table>

<table class="plain-table" style="margin-bottom: 20px;">
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
    @foreach($dospem as $d)
    <tr>
        <td class="bold">Dosen Pembimbing {{ $d->pivot?->urutan == 2 ? 'II' : 'I' }}</td>
        <td>: {{ $d->name }}{{ $d->title ? ', '.$d->title : '' }}</td>
    </tr>
    @endforeach
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

<table class="plain-table" style="margin-top: 16px;">
    <tr>
        <td class="bold">{{ $labelNilai }} = &#8721; nilai / {{ $assessmentForm->template->nilai_penyebut }} &times; {{ $assessmentForm->template->nilai_pengali }}</td>
        <td class="bold center" style="width: 100px;">{{ $assessmentForm->skor_total }}</td>
    </tr>
</table>

@if($assessmentForm->catatan)
<table class="plain-table" style="margin-top: 10px;">
    <tr>
        <td class="bold">CATATAN :</td>
        <td>{{ $assessmentForm->catatan }}</td>
    </tr>
</table>
@endif

<table class="sig-table" style="margin-top: 24px;">
    <tr>
        <td></td>
        <td style="text-align: right;">{{ 'Jombang, '.$tanggal }}</td>
    </tr>
    <tr>
        <td></td>
        <td class="bold" style="text-align: right;">{{ $tipeLabelDetail }}</td>
    </tr>
    <tr>
        <td style="height: 90px;"></td>
        <td style="text-align: right;">
            <img src="{{ $qrService->penilaianSignature($assessmentForm) }}" alt="QR Tanda Tangan Elektronik" style="width: 80px; height: 80px;">
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: right; padding-right: 40px;">{{ $assessmentForm->dosen->name }}{{ $assessmentForm->dosen->title ? ', '.$assessmentForm->dosen->title : '' }}</td>
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
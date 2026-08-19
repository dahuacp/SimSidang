# Rekap Hasil Penilaian — Design Spec

**Goal:** Build admin report page for aggregated assessment results with export Excel.

## Arkitektur

- Controller baru `Admin\RekapController` 
- View baru `admin/rekap/nilai.blade.php` + AJAX untuk export
- Config baru `config/penilaian.php` untuk bobot dospem/penguji
- Re-use infrastructure export: `Maatwebsite\Excel` (sudah ada), `Barryvdh\DomPDF` (sudah ada)

## Data Flow

1. Query `assessment_forms` + eager load submission, user, dosen, template, schedule
2. Group by `submission_id`
3. Hitung persentase tiap form: `skor_total / max_skor × 100`
   - max_skor = sum(items.*.maksimal) per template
4. Hitung rata-dospem (dospem I + II) & rata-penguji
5. Hitung nilai akhir: rata-dospem × bobot_dospem% + rata-penguji × bobot_penguji%
6. Tampil di tabel + filter prodi + sort nilai
7. Export Excel menggunakan `Sheets` custom

## Formula Persentase

```php
$persentase = $form->skor_total / collect($form->template->items)->sum('maksimal') * 100;
```

## File Dibuat/Diubah

### File Baru:
- `config/penilaian.php` — bobot konfigurasi (50/50 default)
- `app/Http/Controllers/Admin/RekapController.php` — index + nilai + exportExcel
- `app/Exports/NilaiRekapExport.php` — Sheet untuk Excel
- `app/Http/Requests/GenerateNilaiRekapRequest.php` — validasi filter prodi (optional)
- `resources/views/admin/rekap/nilai.blade.php` — tabel + grafik ApexCharts
- `tests/Feature/RekapNilaiTest.php` — 3 test (index, export, filter)

### Route:
- `GET /admin/rekap/nilai` → `RekapController@index`
- `GET /admin/rekap/nilai-excel` → `RekapController@exportExcel`

### Komponen Frontend:
- Dropdown filter prodi (reusable dropdown dari template)
- Sorting kolom (ascending/descending)
- Badge nilai (A/B/C/D/E berdasarkan rentang)
- Donut chart distribusi nilai
- Bar chart rata nilai per prodi
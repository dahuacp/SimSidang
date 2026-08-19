<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prodi_id' => [
                'required',
                'integer',
                Rule::exists('prodis', 'id'),
                Rule::unique('assessment_templates', 'prodi_id')
                    ->where('jenis_sidang_id', $this->input('jenis_sidang_id'))
                    ->where('tipe_penilai', $this->input('tipe_penilai')),
            ],
            'jenis_sidang_id' => ['required', 'integer', Rule::exists('jenis_sidangs', 'id')],
            'tipe_penilai' => ['required', Rule::in(['dospem', 'penguji'])],
            'nama' => ['required', 'string', 'max:255'],
            'nilai_penyebut' => ['required', 'integer', 'min:1'],
            'nilai_pengali' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.maksimal' => ['required', 'integer', 'min:1'],
            'items.*.urutan' => ['required', 'integer', 'min:1'],
            'items.*.bobot' => ['nullable', 'integer', 'min:0'],
            'items.*.deskripsi' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'prodi_id.required' => 'Program studi wajib dipilih.',
            'prodi_id.exists' => 'Program studi yang dipilih tidak valid.',
            'prodi_id.unique' => 'Template untuk kombinasi prodi, jenis sidang, dan tipe penilai ini sudah ada.',
            'jenis_sidang_id.required' => 'Jenis sidang wajib dipilih.',
            'jenis_sidang_id.exists' => 'Jenis sidang yang dipilih tidak valid.',
            'tipe_penilai.required' => 'Tipe penilai wajib dipilih.',
            'tipe_penilai.in' => 'Tipe penilai tidak valid.',
            'nama.required' => 'Nama template wajib diisi.',
            'nilai_penyebut.required' => 'Nilai penyebut (A) wajib diisi.',
            'nilai_penyebut.min' => 'Nilai penyebut (A) harus ≥ 1.',
            'nilai_pengali.required' => 'Nilai pengali (B) wajib diisi.',
            'nilai_pengali.min' => 'Nilai pengali (B) tidak boleh negatif.',
            'items.required' => 'Minimal satu item penilaian wajib ditambahkan.',
            'items.min' => 'Minimal satu item penilaian wajib ditambahkan.',
            'items.*.name.required' => 'Nama item penilaian wajib diisi.',
            'items.*.maksimal.required' => 'Nilai maksimal item wajib diisi.',
            'items.*.maksimal.min' => 'Nilai maksimal item harus ≥ 1.',
            'items.*.urutan.required' => 'Urutan item wajib diisi.',
            'items.*.urutan.min' => 'Urutan item harus ≥ 1.',
            'items.*.bobot.min' => 'Bobot item tidak boleh negatif.',
        ];
    }
}

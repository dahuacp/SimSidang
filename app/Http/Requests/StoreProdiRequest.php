<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_prodi' => ['required', 'string', 'max:20', 'unique:prodis,kode_prodi'],
            'nama_prodi' => ['required', 'string', 'max:255', 'unique:prodis,nama_prodi'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_prodi.required' => 'Kode prodi wajib diisi.',
            'kode_prodi.unique' => 'Kode prodi sudah terdaftar.',
            'nama_prodi.required' => 'Nama prodi wajib diisi.',
            'nama_prodi.unique' => 'Nama prodi sudah terdaftar.',
        ];
    }
}

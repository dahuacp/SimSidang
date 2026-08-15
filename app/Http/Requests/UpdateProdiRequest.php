<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prodiId = $this->route('prodi')->id;

        return [
            'kode_prodi' => ['required', 'string', 'max:20', Rule::unique('prodis', 'kode_prodi')->ignore($prodiId)],
            'nama_prodi' => ['required', 'string', 'max:255', Rule::unique('prodis', 'nama_prodi')->ignore($prodiId)],
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

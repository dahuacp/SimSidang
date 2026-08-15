<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJenisSidangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', 'unique:jenis_sidangs,nama'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jenis sidang wajib diisi.',
            'nama.unique' => 'Nama jenis sidang sudah terdaftar.',
        ];
    }
}

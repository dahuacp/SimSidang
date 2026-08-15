<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJenisSidangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('jenis_sidang')->id;

        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('jenis_sidangs', 'nama')->ignore($id)],
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

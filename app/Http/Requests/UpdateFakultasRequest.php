<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFakultasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fakultasId = $this->route('fakultas')->id;

        return [
            'kode_fakultas' => ['required', 'string', 'max:20', Rule::unique('fakultas', 'kode_fakultas')->ignore($fakultasId)],
            'nama_fakultas' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_fakultas.required' => 'Kode fakultas wajib diisi.',
            'kode_fakultas.unique' => 'Kode fakultas sudah terdaftar.',
            'nama_fakultas.required' => 'Nama fakultas wajib diisi.',
        ];
    }
}

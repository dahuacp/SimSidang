<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['mahasiswa', 'dosen', 'admin'])],
            'prodi_id' => [
                Rule::requiredIf(in_array($this->input('role'), ['mahasiswa', 'dosen'])),
                'exists:prodis,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'username.required' => 'NIM/NIDN wajib diisi.',
            'username.unique' => 'NIM/NIDN sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran tidak valid.',
            'prodi_id.required' => 'Program studi wajib dipilih untuk mahasiswa dan dosen.',
            'prodi_id.exists' => 'Program studi tidak valid.',
        ];
    }
}

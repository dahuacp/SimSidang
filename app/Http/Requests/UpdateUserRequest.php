<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');
        $role = $this->input('role') ?? ($this->route('user')->role ?? 'mahasiswa');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(['mahasiswa', 'dosen', 'admin'])],
            'prodi_id' => [
                Rule::requiredIf(in_array($role, ['mahasiswa', 'dosen'])),
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
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran tidak valid.',
            'prodi_id.required' => 'Program studi wajib dipilih untuk mahasiswa dan dosen.',
            'prodi_id.exists' => 'Program studi tidak valid.',
        ];
    }
}

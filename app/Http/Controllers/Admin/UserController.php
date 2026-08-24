<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');
        $sortBy = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');
        $role = $request->input('role');

        $allowedSorts = ['name', 'username', 'role', 'created_at'];
        $allowedDir = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'name';
        $sortDir = in_array($sortDir, $allowedDir) ? $sortDir : 'asc';

        $users = User::with('prodi')
            ->when($search, fn ($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%"))
            ->when($role, fn ($q, $r) => $q->where('role', $r))
            ->orderBy($sortBy, $sortDir)
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'sortBy', 'sortDir', 'role'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'prodi_id' => $request->prodi_id,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('viewAdminMenu', User::class);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'prodi_id' => $request->prodi_id,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorize('viewAdminMenu', User::class);

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJenisSidangRequest;
use App\Http\Requests\UpdateJenisSidangRequest;
use App\Models\JenisSidang;
use App\Models\User;
use Illuminate\Http\Request;

class JenisSidangController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');
        $sortBy = $request->input('sort', 'nama');
        $sortDir = $request->input('dir', 'asc');

        $allowedSorts = ['nama', 'deskripsi'];
        $allowedDir = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'nama';
        $sortDir = in_array($sortDir, $allowedDir) ? $sortDir : 'asc';

        $jenisSidangs = JenisSidang::when($search, fn ($q, $s) => $q
            ->where('nama', 'like', "%{$s}%")
            ->orWhere('deskripsi', 'like', "%{$s}%"))
            ->withCount('schedules')
            ->orderBy($sortBy, $sortDir)
            ->paginate(15)
            ->withQueryString();

        return view('admin.jenis-sidangs.index', compact('jenisSidangs', 'search', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.jenis-sidangs.create');
    }

    public function store(StoreJenisSidangRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        JenisSidang::create($request->validated());

        return redirect()->route('admin.jenis-sidangs.index')->with('success', 'Jenis sidang berhasil ditambahkan.');
    }

    public function edit(JenisSidang $jenis_sidang)
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.jenis-sidangs.edit', compact('jenis_sidang'));
    }

    public function update(UpdateJenisSidangRequest $request, JenisSidang $jenis_sidang)
    {
        $this->authorize('viewAdminMenu', User::class);

        $jenis_sidang->update($request->validated());

        return redirect()->route('admin.jenis-sidangs.index')->with('success', 'Jenis sidang berhasil diperbarui.');
    }

    public function destroy(JenisSidang $jenis_sidang)
    {
        $this->authorize('viewAdminMenu', User::class);

        if ($jenis_sidang->schedules()->exists()) {
            return back()->with('error', 'Jenis sidang tidak dapat dihapus karena masih dipakai oleh jadwal sidang.');
        }

        $jenis_sidang->delete();

        return back()->with('success', 'Jenis sidang berhasil dihapus.');
    }
}

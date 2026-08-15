<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdiRequest;
use App\Http\Requests\UpdateProdiRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\Request;

class ProdiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');

        $prodis = Prodi::when($search, fn ($q, $s) => $q->where('nama_prodi', 'like', "%$s%")->orWhere('kode_prodi', 'like', "%$s%"))
            ->withCount('users')
            ->orderBy('nama_prodi')
            ->paginate(15)
            ->withQueryString();

        return view('admin.prodis.index', compact('prodis', 'search'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.prodis.create');
    }

    public function store(StoreProdiRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        Prodi::create([
            'kode_prodi' => $request->kode_prodi,
            'nama_prodi' => $request->nama_prodi,
        ]);

        return redirect()->route('admin.prodis.index')->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function edit(Prodi $prodi)
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.prodis.edit', compact('prodi'));
    }

    public function update(UpdateProdiRequest $request, Prodi $prodi)
    {
        $this->authorize('viewAdminMenu', User::class);

        $prodi->update([
            'kode_prodi' => $request->kode_prodi,
            'nama_prodi' => $request->nama_prodi,
        ]);

        return redirect()->route('admin.prodis.index')->with('success', 'Program studi berhasil diperbarui.');
    }

    public function destroy(Prodi $prodi)
    {
        $this->authorize('viewAdminMenu', User::class);

        if ($prodi->users()->exists()) {
            return back()->with('error', 'Program studi tidak dapat dihapus karena masih digunakan oleh pengguna.');
        }

        $prodi->delete();

        return back()->with('success', 'Program studi berhasil dihapus.');
    }
}

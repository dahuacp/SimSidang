<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdiRequest;
use App\Http\Requests\UpdateProdiRequest;
use App\Models\Fakultas;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\Request;

class ProdiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');
        $sortBy = $request->input('sort', 'nama_prodi');
        $sortDir = $request->input('dir', 'asc');
        $fakultas = $request->input('fakultas_id');

        $allowedSorts = ['kode_prodi', 'nama_prodi'];
        $allowedDir = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'nama_prodi';
        $sortDir = in_array($sortDir, $allowedDir) ? $sortDir : 'asc';

        $prodis = Prodi::with('fakultas')
            ->when($search, fn ($q, $s) => $q->where('nama_prodi', 'like', "%$s%")->orWhere('kode_prodi', 'like', "%$s%"))
            ->withCount('users')
            ->when($fakultas, fn ($q, $v) => $q->where('fakultas_id', $v))
            ->orderBy($sortBy, $sortDir)
            ->paginate(15)
            ->withQueryString();

        $fakultasList = Fakultas::pluck('nama_fakultas', 'id');

        return view('admin.prodis.index', compact('prodis', 'search', 'sortBy', 'sortDir', 'fakultas', 'fakultasList'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        $fakultas = Fakultas::orderBy('nama_fakultas')->get();

        return view('admin.prodis.create', compact('fakultas'));
    }

    public function store(StoreProdiRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        Prodi::create([
            'kode_prodi' => $request->kode_prodi,
            'nama_prodi' => $request->nama_prodi,
            'fakultas_id' => $request->fakultas_id,
        ]);

        return redirect()->route('admin.prodis.index')->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function edit(Prodi $prodi)
    {
        $this->authorize('viewAdminMenu', User::class);

        $fakultas = Fakultas::orderBy('nama_fakultas')->get();

        return view('admin.prodis.edit', compact('prodi', 'fakultas'));
    }

    public function update(UpdateProdiRequest $request, Prodi $prodi)
    {
        $this->authorize('viewAdminMenu', User::class);

        $prodi->update([
            'kode_prodi' => $request->kode_prodi,
            'nama_prodi' => $request->nama_prodi,
            'fakultas_id' => $request->fakultas_id,
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

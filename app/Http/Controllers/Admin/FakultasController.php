<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFakultasRequest;
use App\Http\Requests\UpdateFakultasRequest;
use App\Models\Fakultas;
use App\Models\User;
use Illuminate\Http\Request;

class FakultasController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');

        $fakultas = Fakultas::when($search, fn ($q, $s) => $q->where('nama_fakultas', 'like', "%$s%")->orWhere('kode_fakultas', 'like', "%$s%"))
            ->withCount('prodis')
            ->orderBy('nama_fakultas')
            ->paginate(15)
            ->withQueryString();

        return view('admin.fakultas.index', compact('fakultas', 'search'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.fakultas.create');
    }

    public function store(StoreFakultasRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        Fakultas::create([
            'kode_fakultas' => $request->kode_fakultas,
            'nama_fakultas' => $request->nama_fakultas,
        ]);

        return redirect()->route('admin.fakultas.index')->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function edit(Fakultas $fakultas)
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.fakultas.edit', compact('fakultas'));
    }

    public function update(UpdateFakultasRequest $request, Fakultas $fakultas)
    {
        $this->authorize('viewAdminMenu', User::class);

        $fakultas->update([
            'kode_fakultas' => $request->kode_fakultas,
            'nama_fakultas' => $request->nama_fakultas,
        ]);

        return redirect()->route('admin.fakultas.index')->with('success', 'Fakultas berhasil diperbarui.');
    }

    public function destroy(Fakultas $fakultas)
    {
        $this->authorize('viewAdminMenu', User::class);

        if ($fakultas->prodis()->exists()) {
            return back()->with('error', 'Fakultas tidak dapat dihapus karena masih memiliki program studi.');
        }

        $fakultas->delete();

        return back()->with('success', 'Fakultas berhasil dihapus.');
    }
}

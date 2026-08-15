<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleMahasiswaRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Imports\ScheduleImport;
use App\Models\JenisSidang;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $search = $request->input('search');

        $schedules = Schedule::withCount('mahasiswas')
            ->with(['dosens', 'jenisSidang'])
            ->when($search, fn ($q, $s) => $q->where('nama_grup_sidang', 'like', "%$s%")->orWhere('ruangan', 'like', "%$s%"))
            ->orderBy('tanggal_sidang', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.schedules.index', compact('schedules', 'search'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        $jenisSidangs = JenisSidang::orderBy('nama')->get();

        return view('admin.schedules.create', compact('jenisSidangs'));
    }

    public function store(StoreScheduleRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule = Schedule::create($request->only(['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai', 'jenis_sidang_id']));

        $schedule->dosens()->sync($request->input('dosens', []));

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->load(['mahasiswas', 'submissions.user', 'jenisSidang']);
        $jenisSidangs = JenisSidang::orderBy('nama')->get();

        return view('admin.schedules.edit', [
            'schedule' => $schedule,
            'jenisSidangs' => $jenisSidangs,
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->update($request->only(['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai', 'jenis_sidang_id']));

        $schedule->dosens()->sync($request->input('dosens', []));

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->dosens()->detach();
        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $import = new ScheduleImport;
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        if (! empty($import->failures)) {
            return back()->with('error', 'Import sebagian gagal: '.implode('; ', $import->failures));
        }

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil diimport.');
    }

    public function template()
    {
        $this->authorize('viewAdminMenu', User::class);

        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'rb+');
            fputcsv($handle, ['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai', 'jenis_sidang', 'dosen_ids']);
            fputcsv($handle, ['Sidang TA Gelombang 1', 'Ruang 3', '2026-08-15', '09:00', '11:00', 'TA', '']);
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="template_jadwal.csv"');

        return $response;
    }

    public function storeMahasiswa(StoreScheduleMahasiswaRequest $request, Schedule $schedule)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->mahasiswas()->attach($request->input('user_id'));

        return back()->with('success', 'Mahasiswa berhasil di-plot ke jadwal.');
    }

    public function destroyMahasiswa(Schedule $schedule, User $user)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->mahasiswas()->detach($user->id);

        return back()->with('success', 'Mahasiswa berhasil dihapus dari jadwal.');
    }

    public function searchUsers(Request $request, ?Schedule $schedule = null)
    {
        $this->authorize('viewAdminMenu', User::class);

        $type = $request->query('type', 'dosen');
        $term = $request->query('term', '');

        if (! in_array($type, ['dosen', 'mahasiswa'])) {
            return response()->json(['error' => 'Tipe pengguna tidak valid.'], 422);
        }

        $query = User::where('role', $type)
            ->when($term, fn ($q, $t) => $q->where(function ($sub) use ($t) {
                $sub->where('name', 'like', "%{$t}%")
                    ->orWhere('username', 'like', "%{$t}%");
            }))
            ->orderBy('name')
            ->limit(21);

        $users = $query->get();

        if ($schedule) {
            $excludeIds = $type === 'dosen'
                ? $schedule->dosens->pluck('id')
                : $schedule->mahasiswas->pluck('id');
            $users = $users->filter(fn ($u) => ! $excludeIds->contains($u->id));
        }

        $hasMore = $users->count() > 20;
        $users = $users->slice(0, 20);

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'label' => "{$u->name} ({$u->username})",
            ]),
            'has_more' => $hasMore,
        ]);
    }
}

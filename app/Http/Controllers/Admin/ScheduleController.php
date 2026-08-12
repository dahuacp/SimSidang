<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleMahasiswaRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Imports\ScheduleImport;
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
            ->with('dosens')
            ->when($search, fn ($q, $s) => $q->where('nama_grup_sidang', 'like', "%$s%")->orWhere('ruangan', 'like', "%$s%"))
            ->orderBy('tanggal_sidang', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.schedules.index', compact('schedules', 'search'));
    }

    public function create()
    {
        $this->authorize('viewAdminMenu', User::class);

        return view('admin.schedules.create', ['dosens' => User::where('role', 'dosen')->orderBy('name')->get()]);
    }

    public function store(StoreScheduleRequest $request)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule = Schedule::create($request->only(['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai']));

        $schedule->dosens()->sync($request->input('dosens', []));

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->load(['mahasiswas', 'submissions.user']);

        return view('admin.schedules.edit', [
            'schedule' => $schedule,
            'dosens' => User::where('role', 'dosen')->orderBy('name')->get(),
            'availableMahasiswas' => User::where('role', 'mahasiswa')
                ->whereNotIn('id', $schedule->mahasiswas->pluck('id'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        $this->authorize('viewAdminMenu', User::class);

        $schedule->update($request->only(['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai']));

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
            fputcsv($handle, ['nama_grup_sidang', 'ruangan', 'tanggal_sidang', 'jam_mulai', 'jam_selesai', 'dosen_ids']);
            fputcsv($handle, ['Sidang TA Gelombang 1', 'Ruang 3', '2026-08-15', '09:00', '11:00', '']);
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
}

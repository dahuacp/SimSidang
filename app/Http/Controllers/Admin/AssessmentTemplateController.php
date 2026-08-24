<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentTemplateRequest;
use App\Http\Requests\UpdateAssessmentTemplateRequest;
use App\Models\AssessmentTemplate;
use App\Models\JenisSidang;
use App\Models\Prodi;
use Illuminate\Http\Request;

class AssessmentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AssessmentTemplate::class);

        $search = $request->input('search');
        $sortBy = $request->input('sort', 'nama');
        $sortDir = $request->input('dir', 'asc');
        $prodi = $request->input('prodi_id');
        $jenisSidang = $request->input('jenis_sidang_id');

        $allowedSorts = ['nama', 'tipe_penilai'];
        $allowedDir = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'nama';
        $sortDir = in_array($sortDir, $allowedDir) ? $sortDir : 'asc';

        $templates = AssessmentTemplate::with(['prodi', 'jenisSidang'])
            ->when($search, fn ($q, $s) => $q
                ->where('nama', 'like', "%{$s}%")
                ->orWhereHas('prodi', fn ($q) => $q->where('nama_prodi', 'like', "%{$s}%"))
                ->orWhereHas('jenisSidang', fn ($q) => $q->where('nama', 'like', "%{$s}%")))
            ->when($prodi, fn ($q, $v) => $q->where('prodi_id', $v))
            ->when($jenisSidang, fn ($q, $v) => $q->where('jenis_sidang_id', $v))
            ->orderBy($sortBy, $sortDir)
            ->paginate(15)
            ->withQueryString();

        $prodis = Prodi::pluck('nama_prodi', 'id');
        $jenisSidangs = JenisSidang::pluck('nama', 'id');

        return view('admin.assessment-templates.index', compact('templates', 'search', 'sortBy', 'sortDir', 'prodi', 'jenisSidang', 'prodis', 'jenisSidangs'));
    }

    public function create()
    {
        $this->authorize('create', AssessmentTemplate::class);

        return view('admin.assessment-templates.create', [
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'jenisSidangs' => JenisSidang::orderBy('nama')->get(),
        ]);
    }

    public function store(StoreAssessmentTemplateRequest $request)
    {
        $this->authorize('create', AssessmentTemplate::class);

        AssessmentTemplate::create($request->validated());

        return redirect()->route('admin.assessment-templates.index')
            ->with('success', 'Template penilaian berhasil ditambahkan.');
    }

    public function edit(AssessmentTemplate $assessment_template)
    {
        $this->authorize('update', $assessment_template);

        return view('admin.assessment-templates.edit', [
            'template' => $assessment_template,
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'jenisSidangs' => JenisSidang::orderBy('nama')->get(),
        ]);
    }

    public function update(UpdateAssessmentTemplateRequest $request, AssessmentTemplate $assessment_template)
    {
        $this->authorize('update', $assessment_template);

        $assessment_template->update($request->validated());

        return redirect()->route('admin.assessment-templates.index')
            ->with('success', 'Template penilaian berhasil diperbarui.');
    }

    public function destroy(AssessmentTemplate $assessment_template)
    {
        $this->authorize('delete', $assessment_template);

        if ($assessment_template->forms()->exists()) {
            return back()->with('error', 'Template tidak dapat dihapus karena sudah digunakan untuk mengisi form penilaian.');
        }

        $assessment_template->delete();

        return back()->with('success', 'Template penilaian berhasil dihapus.');
    }
}

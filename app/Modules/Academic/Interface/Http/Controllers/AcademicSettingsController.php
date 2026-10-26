<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeViewer($request);
        $schoolId = (int) $request->user()->school_id;

        return Inertia::render('Academic/Settings/Index', [
            'academic_years' => AcademicYear::where('school_id', $schoolId)->with('terms')->latest('start_date')->get(),
            'subjects' => Subject::where('school_id', $schoolId)->orderBy('order')->orderBy('name')->get(),
            'can_manage' => $request->user()->hasRole(['admin', 'operator']),
        ]);
    }

    public function storeYear(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('academic_years')->where('school_id', $schoolId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);
        $year = AcademicYear::create([...$data, 'school_id' => $schoolId, 'is_active' => false]);
        $this->audit->log('academic.year.created', 'AcademicYear', (string) $year->id, $data, actor: $request->user(), schoolId: $schoolId);

        return back()->with('message', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function updateYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorizeOwned($request, $academicYear->school_id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('academic_years')->where('school_id', $academicYear->school_id)->ignore($academicYear->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);
        $old = $academicYear->only(array_keys($data));
        $academicYear->update($data);
        $this->audit->log('academic.year.updated', 'AcademicYear', (string) $academicYear->id, $data, $old, $request->user(), (int) $academicYear->school_id);

        return back()->with('message', 'Tahun ajaran berhasil diperbarui.');
    }

    public function activateYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorizeOwned($request, $academicYear->school_id);
        DB::transaction(function () use ($academicYear) {
            AcademicYear::where('school_id', $academicYear->school_id)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });

        return back()->with('message', 'Tahun ajaran aktif berhasil diubah.');
    }

    public function storeTerm(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorizeOwned($request, $academicYear->school_id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('terms')->where('academic_year_id', $academicYear->id)],
            'semester_type' => ['required', Rule::in(['odd', 'even'])],
            'start_date' => ['required', 'date', 'after_or_equal:'.$academicYear->start_date->format('Y-m-d')],
            'end_date' => ['required', 'date', 'after:start_date', 'before_or_equal:'.$academicYear->end_date->format('Y-m-d')],
        ]);
        $academicYear->terms()->create([...$data, 'is_active' => false]);

        return back()->with('message', 'Semester berhasil ditambahkan.');
    }

    public function activateTerm(Request $request, Term $term): RedirectResponse
    {
        $term->load('academicYear');
        $this->authorizeOwned($request, $term->academicYear->school_id);
        DB::transaction(function () use ($term) {
            Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $term->academicYear->school_id))->update(['is_active' => false]);
            AcademicYear::where('school_id', $term->academicYear->school_id)->update(['is_active' => false]);
            $term->academicYear->update(['is_active' => true]);
            $term->update(['is_active' => true]);
        });

        return back()->with('message', 'Semester aktif berhasil diubah.');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $schoolId = (int) $request->user()->school_id;
        $data = $this->validateSubject($request, $schoolId);
        Subject::create([...$data, 'school_id' => $schoolId]);

        return back()->with('message', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorizeOwned($request, $subject->school_id);
        $subject->update($this->validateSubject($request, (int) $subject->school_id, $subject->id));

        return back()->with('message', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroySubject(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorizeOwned($request, $subject->school_id);
        if ($subject->teachingAssignments()->exists()) {
            return back()->with('error', 'Mata pelajaran masih digunakan dalam penugasan mengajar.');
        }
        $subject->delete();

        return back()->with('message', 'Mata pelajaran berhasil dihapus.');
    }

    private function validateSubject(Request $request, int $schoolId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('subjects')->where('school_id', $schoolId)->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator']), 403);
    }

    private function authorizeViewer(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'principal']), 403);
    }

    private function authorizeOwned(Request $request, int $schoolId): void
    {
        $this->authorizeManager($request);
        abort_unless((int) $request->user()->school_id === $schoolId, 404);
    }
}

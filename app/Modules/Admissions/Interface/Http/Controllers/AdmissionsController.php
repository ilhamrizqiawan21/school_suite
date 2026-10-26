<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Application\Services\TeacherDirectoryService;
use App\Modules\Admissions\Application\Policies\AdmissionsPolicy;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionsController extends Controller
{
    public function __construct(private readonly TeacherDirectoryService $teacherDirectory) {}

    public function index(Request $request, AdmissionsPolicy $policy): Response
    {
        Gate::authorize('viewAny', AdmissionApplicant::class);
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $applicants = AdmissionApplicant::query()
            ->where('school_id', $schoolId)
            ->latest('created_at')
            ->get();

        $committee = DB::table('admission_committee_members')
            ->join('users', 'users.id', '=', 'admission_committee_members.user_id')
            ->where('admission_committee_members.school_id', $schoolId)
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'admission_committee_members.created_at as assigned_at']);

        $teachers = $user->hasRole('admin')
            ? $this->teacherDirectory->activeTeacherUsersForSchool($schoolId, $committee->pluck('id')->map(fn ($id) => (int) $id)->all())
            : collect();

        return Inertia::render('Admissions/Index', [
            'applicants' => $applicants,
            'committee' => $committee,
            'available_teachers' => $teachers,
            'can_manage_committee' => $policy->manageCommittee($user),
            'statuses' => [
                ['value' => 'registered', 'label' => 'Terdaftar'],
                ['value' => 'documents_verified', 'label' => 'Berkas terverifikasi'],
                ['value' => 'needs_revision', 'label' => 'Perlu perbaikan'],
                ['value' => 'accepted', 'label' => 'Diterima'],
                ['value' => 'rejected', 'label' => 'Tidak diterima'],
                ['value' => 're_registered', 'label' => 'Daftar ulang'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', AdmissionApplicant::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'previous_school' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        AdmissionApplicant::create([
            ...$validated,
            'school_id' => $request->user()->school_id,
            'registration_no' => 'SPMB-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
            'created_by' => $request->user()->id,
            'status' => 'registered',
        ]);

        return back()->with('message', 'Pendaftar SPMB berhasil ditambahkan.');
    }

    public function update(Request $request, AdmissionApplicant $applicant): RedirectResponse
    {
        Gate::authorize('manageApplicant', $applicant);
        $validated = $request->validate([
            'status' => ['required', 'in:registered,documents_verified,needs_revision,accepted,rejected,re_registered'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $applicant->update($validated);

        return back()->with('message', 'Status pendaftar berhasil diperbarui.');
    }

    public function assign(Request $request): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $validated = $request->validate(['user_id' => ['required', 'integer']]);
        $schoolId = (int) $request->user()->school_id;
        $teacherUser = User::query()
            ->whereKey($validated['user_id'])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'teacher'))
            ->firstOrFail();
        abort_unless($this->teacherDirectory->isActiveTeacherUser($schoolId, (int) $teacherUser->id), 404);

        DB::table('admission_committee_members')->updateOrInsert(
            ['school_id' => $schoolId, 'user_id' => $teacherUser->id],
            ['assigned_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]
        );

        return back()->with('message', 'Guru ditugaskan sebagai panitia SPMB.');
    }

    public function removeCommitteeMember(Request $request, User $member): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        DB::table('admission_committee_members')
            ->where('school_id', $request->user()->school_id)
            ->where('user_id', $member->id)
            ->delete();

        return back()->with('message', 'Penugasan panitia berhasil dicabut.');
    }
}

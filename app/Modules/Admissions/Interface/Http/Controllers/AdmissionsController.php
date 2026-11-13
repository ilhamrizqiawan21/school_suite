<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Application\Services\TeacherDirectoryService;
use App\Modules\Admissions\Application\Policies\AdmissionsPolicy;
use App\Modules\Admissions\Application\Services\AdmissionWorkflowService;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicantDocument;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicantScore;
use App\Modules\Admissions\Infrastructure\Models\AdmissionDocumentRequirement;
use App\Modules\Admissions\Infrastructure\Models\AdmissionPeriod;
use App\Modules\Admissions\Infrastructure\Models\AdmissionSelectionCriterion;
use App\Modules\Admissions\Infrastructure\Models\AdmissionTrack;
use App\Modules\Core\Application\Services\SchoolScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AdmissionsController extends Controller
{
    public function __construct(
        private readonly TeacherDirectoryService $teacherDirectory,
        private readonly AdmissionWorkflowService $workflow,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function index(Request $request, AdmissionsPolicy $policy): Response
    {
        return $this->render($request, $policy, 'dashboard');
    }

    public function publicIndex(): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $periods = AdmissionPeriod::query()
            ->where('school_id', $school['id'])
            ->whereIn('status', ['registration_open', 'verification', 'selection', 'announcement', 're_registration'])
            ->with(['tracks' => fn ($query) => $query->where('is_active', true)->with('requirements')])
            ->orderByDesc('registration_start')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AdmissionPeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'academic_year' => $period->academic_year,
                'level' => $period->level,
                'status' => $period->status,
                'registration_start' => $period->registration_start?->toDateString(),
                'registration_end' => $period->registration_end?->toDateString(),
                'verification_start' => $period->verification_start?->toDateString(),
                'verification_end' => $period->verification_end?->toDateString(),
                'selection_start' => $period->selection_start?->toDateString(),
                'selection_end' => $period->selection_end?->toDateString(),
                'announcement_at' => $period->announcement_at?->toDateString(),
                're_registration_start' => $period->re_registration_start?->toDateString(),
                're_registration_end' => $period->re_registration_end?->toDateString(),
                'tracks' => $period->tracks->map(fn (AdmissionTrack $track): array => [
                    'id' => $track->id,
                    'name' => $track->name,
                    'code' => $track->code,
                    'quota' => $track->quota,
                    'requirements' => $track->requirements->map(fn (AdmissionDocumentRequirement $requirement): array => [
                        'label' => $requirement->label,
                        'is_required' => $requirement->is_required,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all();

        return Inertia::render('Public/Admissions/Index', [
            'school' => [
                'name' => $school['name'],
                'level' => $school['level'],
                'address' => $school['address'],
                'phone' => $school['phone'],
                'email' => $school['email'],
                'logo_url' => $school['logo_path'] ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'],
            ],
            'periods' => $periods,
        ]);
    }

    public function section(Request $request, AdmissionsPolicy $policy, string $section): Response
    {
        return $this->render($request, $policy, $section);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'academic_year' => ['required', 'string', 'max:20'],
            'level' => ['nullable', 'string', 'max:10'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'verification_start' => ['nullable', 'date'],
            'verification_end' => ['nullable', 'date', 'after_or_equal:verification_start'],
            'selection_start' => ['nullable', 'date'],
            'selection_end' => ['nullable', 'date', 'after_or_equal:selection_start'],
            'announcement_at' => ['nullable', 'date'],
            're_registration_start' => ['nullable', 'date'],
            're_registration_end' => ['nullable', 'date', 'after_or_equal:re_registration_start'],
            'guideline_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $file = $validated['guideline_file'] ?? null;
        unset($validated['guideline_file']);
        if ($file !== null) {
            $validated['guideline_path'] = $file->store('admissions/'.(int) $request->user()->school_id.'/guidelines', 'local');
        }
        AdmissionPeriod::create([...$validated, 'school_id' => $request->user()->school_id]);

        return back()->with('message', 'Periode SPMB berhasil dibuat.');
    }

    public function updatePeriodStatus(Request $request, AdmissionPeriod $period): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $this->assertPeriodSchool($request, $period);
        $validated = $request->validate(['status' => ['required', Rule::in(['draft', 'registration_open', 'verification', 'selection', 'announcement', 're_registration', 'completed', 'archived'])]]);

        try {
            if ($validated['status'] === 'registration_open') {
                $conflict = AdmissionPeriod::query()
                    ->where('school_id', $period->school_id)
                    ->where('academic_year', $period->academic_year)
                    ->where('level', $period->level)
                    ->where('id', '!=', $period->id)
                    ->whereIn('status', ['registration_open', 'verification', 'selection', 'announcement', 're_registration'])
                    ->exists();
                if ($conflict) {
                    throw new InvalidArgumentException('Sudah ada periode aktif untuk tahun ajaran dan jenjang ini.');
                }
                $this->workflow->openPeriod($period, $request->user());
            } else {
                $period->update(['status' => $validated['status']]);
            }
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        return back()->with('message', 'Status periode SPMB diperbarui.');
    }

    public function storeTrack(Request $request): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $schoolId = (int) $request->user()->school_id;
        $validated = $request->validate([
            'period_id' => ['required', 'integer', Rule::exists('admission_periods', 'id')->where('school_id', $schoolId)],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'quota' => ['required', 'integer', 'min:1'],
        ]);
        AdmissionTrack::create($validated);

        return back()->with('message', 'Jalur SPMB berhasil ditambahkan.');
    }

    public function storeRequirement(Request $request): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $track = AdmissionTrack::with('period')->findOrFail((int) $request->input('track_id'));
        $this->assertPeriodSchool($request, $track->period);
        $validated = $request->validate([
            'track_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:150'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        AdmissionDocumentRequirement::create([...$validated, 'is_required' => (bool) ($validated['is_required'] ?? false)]);

        return back()->with('message', 'Persyaratan berkas berhasil ditambahkan.');
    }

    public function storeCriterion(Request $request): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $track = AdmissionTrack::with('period')->findOrFail((int) $request->input('track_id'));
        $this->assertPeriodSchool($request, $track->period);
        $validated = $request->validate([
            'track_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:150'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'max_score' => ['required', 'numeric', 'min:0.01'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        AdmissionSelectionCriterion::create($validated);

        return back()->with('message', 'Kriteria seleksi berhasil ditambahkan.');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', AdmissionApplicant::class);
        $schoolId = (int) $request->user()->school_id;
        $validated = $request->validate([
            'period_id' => ['nullable', 'integer', Rule::exists('admission_periods', 'id')->where('school_id', $schoolId)],
            'track_id' => ['nullable', 'integer', Rule::exists('admission_tracks', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'previous_school' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (! empty($validated['period_id']) || ! empty($validated['track_id'])) {
            if (empty($validated['period_id']) || empty($validated['track_id'])) {
                throw ValidationException::withMessages(['period_id' => 'Periode dan jalur wajib dipilih bersama.']);
            }
            $period = AdmissionPeriod::query()->where('school_id', $schoolId)->findOrFail($validated['period_id']);
            AdmissionTrack::query()->where('period_id', $period->id)->findOrFail($validated['track_id']);
            if ($validated['nisn'] !== null && AdmissionApplicant::where('school_id', $schoolId)->where('period_id', $period->id)->where('nisn', $validated['nisn'])->exists()) {
                throw ValidationException::withMessages(['nisn' => 'NISN sudah digunakan pada periode SPMB ini.']);
            }
        }

        $validated['school_id'] = $schoolId;
        $validated['registration_no'] = 'SPMB-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'registered';
        AdmissionApplicant::create($validated);

        return back()->with('message', 'Pendaftar SPMB berhasil ditambahkan.');
    }

    public function update(Request $request, AdmissionApplicant $applicant): RedirectResponse
    {
        Gate::authorize('manageApplicant', $applicant);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['registered', 'documents_verified', 'needs_revision', 'selection_review', 'accepted', 'waitlisted', 'rejected', 're_registered'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $this->assertApplicantSchool($request, $applicant);
        $applicant->update($validated);

        return back()->with('message', 'Status pendaftar berhasil diperbarui.');
    }

    public function storeDocument(Request $request, AdmissionApplicant $applicant): RedirectResponse
    {
        Gate::authorize('manageApplicant', $applicant);
        $this->assertApplicantSchool($request, $applicant);
        $validated = $request->validate([
            'requirement_id' => ['nullable', 'integer', Rule::exists('admission_document_requirements', 'id')],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $requirement = ! empty($validated['requirement_id']) ? AdmissionDocumentRequirement::findOrFail($validated['requirement_id']) : null;
        if ($requirement !== null && (int) $requirement->track_id !== (int) $applicant->track_id) {
            abort(422, 'Persyaratan berkas tidak sesuai jalur pendaftar.');
        }
        $file = $validated['file'];
        $path = $file->store('admissions/'.$applicant->school_id.'/'.$applicant->id, 'local');
        AdmissionApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'requirement_id' => $requirement?->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('message', 'Berkas pendaftar berhasil diunggah.');
    }

    public function verifyDocument(Request $request, AdmissionApplicantDocument $document): RedirectResponse
    {
        Gate::authorize('manageApplicant', $document->applicant);
        $this->assertApplicantSchool($request, $document->applicant);
        $validated = $request->validate(['status' => ['required', Rule::in(['valid', 'needs_revision', 'rejected'])], 'notes' => ['nullable', 'string', 'max:3000']]);
        $document->update([...$validated, 'verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('message', 'Status berkas berhasil diperbarui.');
    }

    public function downloadDocument(Request $request, AdmissionApplicantDocument $document)
    {
        Gate::authorize('manageApplicant', $document->applicant);
        $this->assertApplicantSchool($request, $document->applicant);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function storeScore(Request $request, AdmissionApplicant $applicant): RedirectResponse
    {
        Gate::authorize('manageApplicant', $applicant);
        $this->assertApplicantSchool($request, $applicant);
        $criterion = AdmissionSelectionCriterion::query()->where('id', $request->integer('criterion_id'))->where('track_id', $applicant->track_id)->firstOrFail();
        $validated = $request->validate(['criterion_id' => ['required', 'integer'], 'score' => ['required', 'numeric', 'min:0', 'max:'.$criterion->max_score], 'notes' => ['nullable', 'string', 'max:3000']]);
        AdmissionApplicantScore::updateOrCreate(['applicant_id' => $applicant->id, 'criterion_id' => $criterion->id], [...$validated, 'scored_by' => $request->user()->id]);

        return back()->with('message', 'Nilai seleksi berhasil disimpan.');
    }

    public function rank(Request $request, AdmissionPeriod $period): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $this->assertPeriodSchool($request, $period);
        $this->workflow->rank($period, $request->user());

        return back()->with('message', 'Ranking SPMB berhasil dihitung.');
    }

    public function finalize(Request $request, AdmissionApplicant $applicant): RedirectResponse
    {
        Gate::authorize('manageApplicant', $applicant);
        $this->assertApplicantSchool($request, $applicant);
        $validated = $request->validate(['decision' => ['required', Rule::in(['accepted', 'waitlisted', 'rejected'])], 'override_reason' => ['required', 'string', 'max:3000']]);
        try {
            $this->workflow->finalize($applicant, $validated['decision'], $validated['override_reason'], $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['decision' => $exception->getMessage()]);
        }

        return back()->with('message', 'Keputusan seleksi berhasil difinalisasi.');
    }

    public function confirmReRegistration(Request $request, AdmissionApplicant $applicant): RedirectResponse
    {
        Gate::authorize('manageApplicant', $applicant);
        $this->assertApplicantSchool($request, $applicant);
        $validated = $request->validate(['nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->where('school_id', $request->user()->school_id)->ignore($applicant->reRegistration?->student_id, 'id')]]);
        try {
            $this->workflow->confirmReRegistration($applicant, $validated['nis'], $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['nis' => $exception->getMessage()]);
        }

        return back()->with('message', 'Daftar ulang dikonfirmasi dan data siswa Akademik dibuat.');
    }

    public function assign(Request $request): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        $validated = $request->validate(['user_id' => ['required', 'integer']]);
        $schoolId = (int) $request->user()->school_id;
        $teacherUser = User::query()->whereKey($validated['user_id'])->where('school_id', $schoolId)->where('is_active', true)->whereHas('role', fn ($query) => $query->where('name', 'teacher'))->firstOrFail();
        abort_unless($this->teacherDirectory->isActiveTeacherUser($schoolId, (int) $teacherUser->id), 404);
        DB::table('admission_committee_members')->updateOrInsert(['school_id' => $schoolId, 'user_id' => $teacherUser->id], ['assigned_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('message', 'Guru ditugaskan sebagai panitia SPMB.');
    }

    public function removeCommitteeMember(Request $request, User $member): RedirectResponse
    {
        Gate::authorize('manageCommittee', AdmissionApplicant::class);
        DB::table('admission_committee_members')->where('school_id', $request->user()->school_id)->where('user_id', $member->id)->delete();

        return back()->with('message', 'Penugasan panitia berhasil dicabut.');
    }

    private function render(Request $request, AdmissionsPolicy $policy, string $section): Response
    {
        Gate::authorize('viewAny', AdmissionApplicant::class);
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $periods = AdmissionPeriod::query()->where('school_id', $schoolId)->with(['tracks.requirements', 'tracks.criteria'])->latest()->get();
        $selectedPeriodId = $request->integer('period_id') ?: $periods->first()?->id;
        $selectedPeriod = $periods->firstWhere('id', $selectedPeriodId);
        $tracks = $selectedPeriod?->tracks ?? collect();
        $applicants = AdmissionApplicant::query()->where('school_id', $schoolId)->when($selectedPeriodId, fn ($query) => $query->where('period_id', $selectedPeriodId))->with(['track', 'period', 'documents.requirement', 'scores.criterion', 'selectionResult', 'reRegistration'])->latest()->get();
        $committee = DB::table('admission_committee_members')->join('users', 'users.id', '=', 'admission_committee_members.user_id')->where('admission_committee_members.school_id', $schoolId)->orderBy('users.name')->get(['users.id', 'users.name', 'users.email', 'admission_committee_members.created_at as assigned_at']);
        $teachers = $user->hasRole(['admin', 'operator']) ? $this->teacherDirectory->activeTeacherUsersForSchool($schoolId, $committee->pluck('id')->map(fn ($id) => (int) $id)->all()) : collect();

        return Inertia::render('Admissions/Index', [
            'section' => $section,
            'periods' => $periods,
            'selected_period_id' => $selectedPeriodId,
            'tracks' => $tracks,
            'applicants' => $applicants,
            'committee' => $committee,
            'available_teachers' => $teachers,
            'can_manage_committee' => $policy->manageCommittee($user),
            'can_manage_applicants' => $user->hasRole(['admin', 'operator']) || $policy->isCommitteeMember($user),
            'statuses' => [
                ['value' => 'registered', 'label' => 'Terdaftar'],
                ['value' => 'documents_verified', 'label' => 'Berkas terverifikasi'],
                ['value' => 'needs_revision', 'label' => 'Perlu perbaikan'],
                ['value' => 'selection_review', 'label' => 'Dalam seleksi'],
                ['value' => 'accepted', 'label' => 'Diterima'],
                ['value' => 'waitlisted', 'label' => 'Cadangan'],
                ['value' => 'rejected', 'label' => 'Tidak diterima'],
                ['value' => 're_registered', 'label' => 'Daftar ulang'],
            ],
            'summary' => [
                'periods' => $periods->count(),
                'applicants' => $applicants->count(),
                'accepted' => $applicants->where('status', 'accepted')->count(),
                're_registered' => $applicants->where('status', 're_registered')->count(),
            ],
        ]);
    }

    private function assertPeriodSchool(Request $request, AdmissionPeriod $period): void
    {
        abort_unless((int) $period->school_id === (int) $request->user()->school_id, 403);
    }

    private function assertApplicantSchool(Request $request, AdmissionApplicant $applicant): void
    {
        abort_unless((int) $applicant->school_id === (int) $request->user()->school_id, 403);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\TeachingPlanDocumentService;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\TeachingDocument;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingPlan;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Str;
use ZipArchive;

class TeachingPlanController extends Controller
{
    private const ARTIFACT_TYPES = ['alokasi_waktu', 'prota', 'promes', 'kktp'];

    public function __construct(private readonly TeachingPlanDocumentService $documentService) {}

    public function index(Request $request): InertiaResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        $filters = $request->validate(['plan_id' => ['nullable', 'integer']]);

        $assignments = $activeTerm && $teacherId
            ? $this->assignments($schoolId, $teacherId, $activeTerm->id)->get()
            : collect();
        $subjects = $assignments
            ->map(fn (TeachingAssignment $assignment) => $assignment->subject)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($subject) => ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code]);
        $gradeLevels = $assignments
            ->map(fn (TeachingAssignment $assignment) => $assignment->classGroup?->grade_level)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $plans = TeachingPlan::query()
            ->with('subject:id,name,code')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId ?? 0)
            ->where('term_id', $activeTerm?->id ?? 0)
            ->orderBy('grade_level')
            ->orderBy('subject_id')
            ->get();
        $selectedPlan = isset($filters['plan_id'])
            ? $plans->firstWhere('id', (int) $filters['plan_id'])
            : $plans->first();
        abort_if(isset($filters['plan_id']) && ! $selectedPlan, 404);

        return Inertia::render('Academic/Planning/Index', [
            'active_term' => $activeTerm ? ['id' => $activeTerm->id, 'name' => $activeTerm->name] : null,
            'subjects' => $subjects,
            'grade_levels' => $gradeLevels,
            'plans' => $plans->map(fn (TeachingPlan $plan) => [
                'id' => $plan->id,
                'subject_id' => $plan->subject_id,
                'subject_name' => $plan->subject?->name ?? 'Mata pelajaran',
                'grade_level' => $plan->grade_level,
                'curriculum' => $plan->curriculum,
                'updated_at' => $plan->updated_at?->toIso8601String(),
            ]),
            'selected_plan' => $selectedPlan ? [
                'id' => $selectedPlan->id,
                'subject_id' => $selectedPlan->subject_id,
                'subject_name' => $selectedPlan->subject?->name ?? 'Mata pelajaran',
                'grade_level' => $selectedPlan->grade_level,
                'curriculum' => $selectedPlan->curriculum,
                'data' => $selectedPlan->data,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');

        $validated = $request->validate($this->rules());
        $this->authorizeAssignment($schoolId, $teacherId, $activeTerm->id, $validated['subject_id'], $validated['grade_level']);

        $planAttributes = [
            'teacher_id' => $teacherId,
            'term_id' => $activeTerm->id,
            'subject_id' => $validated['subject_id'],
            'grade_level' => $validated['grade_level'],
        ];
        $existing = TeachingPlan::query()->where($planAttributes)->first();
        $plan = TeachingPlan::updateOrCreate(
            $planAttributes,
            [
                'school_id' => $schoolId,
                'curriculum' => $validated['curriculum'],
                'data' => $this->normalizeData($validated['content'] ?? [], $existing?->data ?? []),
            ]
        );

        return redirect()->route('academic.plans.index', ['plan_id' => $plan->id])
            ->with('message', 'Rencana pembelajaran berhasil disimpan.');
    }

    public function update(Request $request, int $planId): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');

        $plan = TeachingPlan::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)
            ->findOrFail($planId);
        $validated = $request->validate($this->rules());
        $this->authorizeAssignment($schoolId, $teacherId, $activeTerm->id, $validated['subject_id'], $validated['grade_level']);

        $plan->update([
            'subject_id' => $validated['subject_id'],
            'grade_level' => $validated['grade_level'],
            'curriculum' => $validated['curriculum'],
            'data' => $this->normalizeData($validated['content'] ?? [], $plan->data ?? []),
        ]);

        return redirect()->route('academic.plans.index', ['plan_id' => $plan->id])
            ->with('message', 'Rencana pembelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, int $planId): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');

        $plan = TeachingPlan::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)
            ->findOrFail($planId);
        $plan->delete();

        return redirect()->route('academic.plans.index')
            ->with('message', 'Rencana pembelajaran berhasil dihapus.');
    }

    public function documents(Request $request): InertiaResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        $documents = TeachingDocument::query()
            ->where('school_id', $schoolId)->where('teacher_id', $teacherId ?? 0)
            ->where('term_id', $activeTerm?->id ?? 0)->latest('updated_at')->get();
        $plans = TeachingPlan::query()->with('subject:id,name,code')->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId ?? 0)->where('term_id', $activeTerm?->id ?? 0)
            ->orderBy('grade_level')->orderBy('subject_id')->get();

        return Inertia::render('Academic/Planning/Documents', [
            'active_term' => $activeTerm ? ['id' => $activeTerm->id, 'name' => $activeTerm->name] : null,
            'plans' => $plans->map(fn (TeachingPlan $plan) => [
                'id' => $plan->id, 'subject_name' => $plan->subject?->name ?? 'Mata pelajaran',
                'grade_level' => $plan->grade_level, 'curriculum' => $plan->curriculum, 'data' => $plan->data ?? [],
            ]),
            'documents' => $documents->map(fn (TeachingDocument $document) => [
                'id' => $document->id,
                'plan_id' => $document->plan_id,
                'type' => $document->type,
                'title' => $document->title,
                'semester' => $document->semester,
                'content' => $document->content ?? [],
                'updated_at' => $document->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    public function moduleAjar(Request $request): InertiaResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        $plans = TeachingPlan::query()->with('subject:id,name,code')
            ->where('school_id', $schoolId)->where('teacher_id', $teacherId ?? 0)
            ->where('term_id', $activeTerm?->id ?? 0)->orderBy('grade_level')->orderBy('subject_id')->get();
        $serialized = $plans->map(fn (TeachingPlan $plan) => [
            'id' => $plan->id,
            'subject_name' => $plan->subject?->name ?? 'Mata pelajaran',
            'grade_level' => $plan->grade_level,
            'curriculum' => $plan->curriculum,
            'data' => array_merge($plan->data ?? [], ['modul' => $plan->data['modul'] ?? []]),
        ]);
        $selectedId = $request->integer('plan_id');

        return Inertia::render('Academic/Planning/ModulAjar', [
            'active_term' => $activeTerm ? ['id' => $activeTerm->id, 'name' => $activeTerm->name] : null,
            'plans' => $serialized,
            'selected_plan' => $selectedId ? $serialized->firstWhere('id', $selectedId) : $serialized->first(),
            'selected_module_id' => null,
            'profile' => ['teacher_name' => $request->user()->name, 'school_type' => 'sekolah'],
        ]);
    }

    public function saveDocument(Request $request): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');
        $validated = $request->validate([
            'type' => ['required', Rule::in(self::ARTIFACT_TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'academic_term_id' => ['required', 'integer', Rule::in([$activeTerm->id])],
            'plan_id' => ['required', 'integer'],
            'semester' => ['nullable', Rule::in(['ganjil', 'genap'])],
            'content' => ['required', 'array', 'max:100'],
        ]);
        $plan = $this->ownedPlan($schoolId, $teacherId, $activeTerm->id, (int) $validated['plan_id']);
        $semester = $this->documentService->normalizeSemester($validated['semester'] ?? null);
        TeachingDocument::query()->updateOrCreate([
            'school_id' => $schoolId, 'teacher_id' => $teacherId, 'term_id' => $activeTerm->id,
            'plan_id' => $plan->id, 'type' => $validated['type'], 'semester' => $semester,
        ], ['title' => $validated['title'], 'content' => $validated['content']]);

        return redirect()->route('academic.documents.index')->with('message', 'Dokumen berhasil disimpan.');
    }

    public function updateDocument(Request $request, int $documentId): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');
        $document = TeachingDocument::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)->findOrFail($documentId);
        $validated = $request->validate([
            'type' => ['required', Rule::in(self::ARTIFACT_TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'academic_term_id' => ['required', 'integer', Rule::in([$activeTerm->id])],
            'plan_id' => ['required', 'integer'],
            'semester' => ['nullable', Rule::in(['ganjil', 'genap'])],
            'content' => ['required', 'array', 'max:100'],
        ]);
        $plan = $this->ownedPlan($schoolId, $teacherId, $activeTerm->id, (int) $validated['plan_id']);
        $document->update(['type' => $validated['type'], 'title' => $validated['title'],
            'plan_id' => $plan->id, 'semester' => $this->documentService->normalizeSemester($validated['semester'] ?? null), 'content' => $validated['content']]);

        return redirect()->route('academic.documents.index')->with('message', 'Dokumen berhasil diperbarui.');
    }

    public function destroyDocument(Request $request, int $documentId): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');
        TeachingDocument::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)->findOrFail($documentId)->delete();

        return redirect()->route('academic.documents.index')->with('message', 'Dokumen berhasil dihapus.');
    }

    public function saveModuleAjar(Request $request): RedirectResponse
    {
        return $this->persistModuleAjar($request);
    }

    public function updateModuleAjar(Request $request, int $planId): RedirectResponse
    {
        return $this->persistModuleAjar($request, $planId);
    }

    public function packagePage(Request $request): InertiaResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');
        $plans = TeachingPlan::query()->with('subject:id,name,code')->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)->where('term_id', $activeTerm->id)
            ->orderBy('grade_level')->orderBy('subject_id')->get();
        $documents = TeachingDocument::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)->get()->groupBy('plan_id');
        $packages = $plans->map(fn (TeachingPlan $plan) => [
            'id' => $plan->id,
            'title' => ($plan->subject?->name ?? 'Mata pelajaran').' · Kelas '.$plan->grade_level,
            'curriculum' => $plan->curriculum,
            'items' => $this->documentService->packageManifest(
                $plan,
                $documents->get($plan->id, collect()),
                $this->sessionsForPlan($schoolId, $teacherId, $activeTerm->id, $plan),
                $this->documentService->normalizeSemester($activeTerm->semester_type),
            ),
        ]);

        return Inertia::render('Academic/Planning/Package', [
            'active_term' => ['id' => $activeTerm->id, 'name' => $activeTerm->name],
            'packages' => $packages,
            'download_url' => route('academic.documents.package.download'),
        ]);
    }

    public function exportPackage(Request $request)
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');
        abort_unless(class_exists(ZipArchive::class), 500, 'Ekstensi ZIP tidak tersedia pada server.');
        $plans = TeachingPlan::query()->with('subject:id,name,code')->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)->where('term_id', $activeTerm->id)->orderBy('grade_level')->orderBy('subject_id')->get();
        $documents = TeachingDocument::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)->get()->groupBy('plan_id');
        abort_if($plans->isEmpty(), 422, 'Buat ATP/Silabus terlebih dahulu sebelum mengunduh paket.');

        $directory = storage_path('app/tmp/teaching-package-'.Str::uuid());
        abort_unless(mkdir($directory, 0700, true), 500, 'Folder paket sementara tidak dapat dibuat.');
        $zipPath = $directory.'.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Paket ZIP tidak dapat dibuat.');
        $manifestLines = ['Paket Administrasi Pembelajaran', 'Guru: '.$request->user()->name, 'Periode: '.$activeTerm->name, 'Dibuat: '.now()->format('d-m-Y H:i'), ''];
        $entryNumber = 0;

        foreach ($plans as $plan) {
            $planDocs = $documents->get($plan->id, collect());
            foreach ($this->documentService->packageManifest(
                $plan,
                $planDocs,
                $this->sessionsForPlan($schoolId, $teacherId, $activeTerm->id, $plan),
                $this->documentService->normalizeSemester($activeTerm->semester_type),
            ) as $item) {
                $manifestLines[] = $plan->subject?->name.' / Kelas '.$plan->grade_level.' — '.$item['label'].': '.($item['status'] === 'ready' ? 'Disiapkan' : 'Dilewati').' ('.$item['reason'].')';
                if ($item['status'] !== 'ready') continue;
                $word = new PhpWord();
                $section = $word->addSection(['marginTop' => 900, 'marginBottom' => 900, 'marginLeft' => 1000, 'marginRight' => 1000]);
                $section->addTitle($item['label'], 1);
                $section->addText(($plan->subject?->name ?? 'Mata pelajaran').' · Kelas '.$plan->grade_level.' · '.$activeTerm->name);
                $this->writePackageItem($section, $item, $plan, $planDocs);
                $entryNumber++;
                $baseName = sprintf('%02d-%s-kelas-%s-%s', $entryNumber, Str::slug($plan->subject?->name ?? 'mapel'), $plan->grade_level, Str::slug($item['label']));
                $docxPath = $directory.'/'.$baseName.'.docx';
                IOFactory::createWriter($word, 'Word2007')->save($docxPath);
                $zip->addFile($docxPath, $baseName.'.docx');
            }
        }
        $zip->addFromString('MANIFEST.txt', implode("\n", $manifestLines));
        $zip->close();
        foreach (glob($directory.'/*.docx') ?: [] as $file) @unlink($file);
        @rmdir($directory);
        $filename = 'Paket-Administrasi-'.Str::slug($request->user()->name).'-'.now()->format('Ymd').'.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    private function persistModuleAjar(Request $request, ?int $routePlanId = null): RedirectResponse
    {
        [$schoolId, $teacherId, $activeTerm] = $this->context($request);
        abort_if(! $teacherId || ! $activeTerm, 403, 'Guru atau periode akademik aktif tidak ditemukan.');
        $validated = $request->validate([
            'plan_id' => ['required', 'integer'],
            'chapter_id' => ['required', 'max:100'],
            'content' => ['required', 'array'],
            'content.bagian' => ['nullable', 'array', 'max:80'],
            'content.bagian.*' => ['nullable', 'string', 'max:4000'],
            'content.langkah' => ['nullable', 'array', 'max:60'],
            'content.langkah.*.topik' => ['nullable', 'string', 'max:3000'],
            'content.langkah.*.pembahasan' => ['nullable', 'string', 'max:3000'],
            'content.langkah.*.pendahuluan' => ['nullable', 'string', 'max:3000'],
            'content.langkah.*.inti' => ['nullable', 'string', 'max:3000'],
            'content.langkah.*.penutup' => ['nullable', 'string', 'max:3000'],
        ]);
        $planId = $routePlanId ?? (int) $validated['plan_id'];
        abort_if($routePlanId && $routePlanId !== (int) $validated['plan_id'], 404);
        $plan = TeachingPlan::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)->findOrFail($planId);
        $data = $plan->data ?? [];
        $chapterId = (string) $validated['chapter_id'];
        abort_unless(collect($data['chapters'] ?? [])->contains(fn (array $chapter) => (string) ($chapter['id'] ?? '') === $chapterId), 422, 'Bab tidak ditemukan pada ATP/Silabus yang dipilih.');
        $data['modul'] = $data['modul'] ?? [];
        $data['modul'][$chapterId] = [
            'bagian' => $validated['content']['bagian'] ?? [],
            'langkah' => array_values($validated['content']['langkah'] ?? []),
        ];
        $plan->update(['data' => $data]);

        return redirect()->route('academic.module-ajar.index', ['plan_id' => $plan->id])->with('message', 'Modul Ajar berhasil disimpan.');
    }

    private function ownedPlan(int $schoolId, int $teacherId, int $termId, int $planId): TeachingPlan
    {
        return TeachingPlan::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId)
            ->where('term_id', $termId)->findOrFail($planId);
    }

    private function sessionsForPlan(int $schoolId, int $teacherId, int $termId, TeachingPlan $plan)
    {
        return LessonSession::query()->with(['journal', 'classGroup:id,name,grade_level', 'subject:id,name'])
            ->where('school_id', $schoolId)->where('teacher_id', $teacherId)->where('term_id', $termId)
            ->where('subject_id', $plan->subject_id)
            ->whereHas('classGroup', fn ($query) => $query->where('school_id', $schoolId)->where('grade_level', $plan->grade_level))
            ->orderBy('date')->orderBy('start_time')->get();
    }

    private function writePackageItem($section, array $item, TeachingPlan $plan, $documents): void
    {
        $data = $plan->data ?? [];
        $id = $item['id'];
        if ($id === '01-acuan') {
            foreach (($data['references'] ?? []) as $reference) {
                $section->addTitle((string) ($reference['name'] ?? 'Acuan'), 2);
                $section->addText((string) ($reference['content'] ?? ''));
            }
            return;
        }
        if ($id === '02-atp') {
            $chapters = collect($data['chapters'] ?? [])->keyBy(fn (array $chapter) => (string) ($chapter['id'] ?? ''));
            foreach ($chapters as $chapterId => $chapter) {
                $section->addTitle((string) ($chapter['title'] ?? 'Bab'), 2);
                foreach (($data['units'] ?? []) as $unit) {
                    if ((string) ($unit['chapter_id'] ?? '') !== (string) $chapterId) continue;
                    $section->addText(trim(($unit['code'] ?? '').' · '.($unit['objective'] ?? '').' · '.($unit['material'] ?? '')));
                }
            }
            return;
        }
        if ($id === '03-alokasi') {
            $allocationDocument = $documents->first(fn (TeachingDocument $saved) => $saved->type === 'alokasi_waktu');
            $allocationContent = $allocationDocument?->content ?? [];
            foreach ($this->documentService->allocation($plan, $allocationContent) as $semester => $row) {
                $month = (int) data_get($allocationContent, "monthly_period.$semester", $semester === 'ganjil' ? 7 : 1);
                $section->addTitle('Semester '.Str::headline($semester), 2);
                $section->addText($row['effective_weeks'].' minggu efektif × '.$row['jp_per_week'].' JP = '.$row['effective_jp'].' JP efektif; rencana '.$row['planned_jp'].' JP; selisih '.$row['delta_jp'].' JP; mulai '.now()->setMonth($month)->translatedFormat('F').'.');
            }
            return;
        }
        if (str_starts_with($id, '11-modul-')) {
            $module = $item['module'] ?? [];
            foreach (($module['bagian'] ?? []) as $key => $value) {
                if (trim((string) $value) !== '') $section->addText(Str::headline((string) $key).': '.$value);
            }
            foreach (($module['langkah'] ?? []) as $index => $step) {
                $section->addTitle('Pertemuan '.($index + 1), 2);
                foreach ($step as $key => $value) if (trim((string) $value) !== '') $section->addText(Str::headline((string) $key).': '.$value);
            }
            return;
        }
        if (str_starts_with($id, '07-pertemuan-') || str_starts_with($id, '08-pertemuan-')) {
            foreach (($item['sessions'] ?? []) as $session) {
                $section->addTitle('Pertemuan ke-'.$session['meeting_number'].' · '.$session['class_name'], 2);
                $section->addText(($session['date'] ?? 'Tanggal tidak tersedia').' · '.substr((string) $session['start_time'], 0, 5).'–'.substr((string) $session['end_time'], 0, 5).' · '.($session['period_count'] ?? 2).' JP');
                foreach ([
                    'Topik' => $session['topic'] ?? null, 'Tema' => $session['theme'] ?? null,
                    'Tujuan pembelajaran' => $session['objectives'] ?? null, 'Kegiatan pembuka' => $session['opening'] ?? null,
                    'Kegiatan inti' => $session['main'] ?? null, 'Kegiatan penutup' => $session['closing'] ?? null,
                    'Catatan' => $session['notes'] ?? null, 'Refleksi' => $session['reflection'] ?? null,
                    'Tindak lanjut' => $session['follow_up'] ?? null,
                ] as $label => $value) {
                    if (trim((string) $value) !== '') $section->addText($label.': '.$value);
                }
                $attendance = $session['attendance_summary'] ?? [];
                if ($attendance) $section->addText('Presensi — hadir: '.($attendance['present'] ?? 0).', sakit: '.($attendance['sick'] ?? 0).', izin: '.($attendance['permitted'] ?? 0).', alpa: '.($attendance['absent'] ?? 0));
            }
            return;
        }

        $document = $documents->first(fn (TeachingDocument $saved) => $saved->id === ($item['document_id'] ?? null));
        if (! $document) return;
        $content = $document->content ?? [];
        if ($document->semester) $section->addText('Semester '.Str::headline($document->semester));
        if ($document->type === 'alokasi_waktu') {
            foreach (['ganjil', 'genap'] as $semester) {
                $weeks = (int) data_get($content, "effective_weeks.$semester", 0);
                $jp = (int) data_get($content, 'jp_per_week', 0);
                $month = (int) data_get($content, "monthly_period.$semester", 0);
                $section->addText('Semester '.Str::headline($semester).': '.$weeks.' minggu efektif × '.$jp.' JP/minggu = '.($weeks * $jp).' JP; mulai '.($month ? now()->setMonth($month)->translatedFormat('F') : 'belum ditentukan').'.');
            }
        } elseif ($document->type === 'prota' || $document->type === 'promes') {
            foreach (($content['chapters'] ?? []) as $chapter) {
                $section->addTitle((string) ($chapter['title'] ?? $chapter['name'] ?? 'Bab'), 2);
                foreach (($content['units'] ?? []) as $unit) {
                    if ((string) ($unit['chapter_id'] ?? '') !== (string) ($chapter['id'] ?? '')) continue;
                    $section->addText(trim(($unit['code'] ?? '').' · '.($unit['objective'] ?? '').' · '.($unit['material'] ?? '').' · '.($unit['jp'] ?? 0).' JP'));
                }
            }
            if ($document->type === 'promes') {
                $semester = (string) $document->semester;
                $allocationDocument = $documents->first(fn (TeachingDocument $saved) => $saved->type === 'alokasi_waktu');
                $allocationContent = $allocationDocument?->content ?? [];
                $allocation = $this->documentService->allocation($plan, $allocationContent)[$semester] ?? null;
                if ($allocation && $allocation['delta_jp'] < 0) {
                    $section->addText('PERHATIAN: Rencana melebihi kapasitas efektif sebanyak '.abs($allocation['delta_jp']).' JP. Materi setelah minggu ke-30 tidak dimasukkan ke grid.');
                }
                foreach ($this->documentService->promesDistribution($plan, $semester, $allocationContent) as $slot) {
                    $items = collect($slot['units'])->map(fn (array $unit) => $unit['title'].' ('.$unit['jp'].' JP)')->implode('; ');
                    if ($items !== '') $section->addText('Bulan '.$slot['month'].', minggu '.$slot['week'].': '.$items);
                }
            }
        } elseif ($document->type === 'kktp') {
            foreach (($content['intervals'] ?? []) as $interval) $section->addText(($interval['label'] ?? '').' ('.($interval['range'] ?? 'Rentang belum ditentukan').'): '.($interval['description'] ?? ''));
            foreach (($content['units'] ?? []) as $unit) $section->addText(($unit['title'] ?? $unit['objective'] ?? 'Unit').' — KKM: '.($unit['kkm'] ?? 'belum ditetapkan'));
        }
    }

    /** @return array{0:int,1:?int,2:?Term} */
    private function context(Request $request): array
    {
        $schoolId = (int) $request->user()->school_id;
        $teacherId = Teacher::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $request->user()->id)
            ->value('id');
        $activeTerm = Term::query()
            ->whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        return [$schoolId, $teacherId ? (int) $teacherId : null, $activeTerm];
    }

    private function assignments(int $schoolId, int $teacherId, int $termId)
    {
        return TeachingAssignment::query()
            ->with(['subject:id,name,code', 'classGroup:id,school_id,grade_level'])
            ->where('teacher_id', $teacherId)
            ->where('term_id', $termId)
            ->whereHas('subject', fn ($query) => $query->where('school_id', $schoolId)->where('is_active', true))
            ->whereHas('classGroup', fn ($query) => $query->where('school_id', $schoolId));
    }

    private function authorizeAssignment(int $schoolId, int $teacherId, int $termId, int $subjectId, int $gradeLevel): void
    {
        abort_unless(
            $this->assignments($schoolId, $teacherId, $termId)
                ->where('subject_id', $subjectId)
                ->whereHas('classGroup', fn ($query) => $query->where('grade_level', $gradeLevel))
                ->exists(),
            403,
            'Mata pelajaran dan tingkat kelas harus sesuai penugasan mengajar aktif.'
        );
    }

    private function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'grade_level' => ['required', 'integer', 'between:1,12'],
            'curriculum' => ['required', Rule::in(['merdeka', 'kbc', 'k13', 'blank'])],
            'content' => ['nullable', 'array'],
            'content.references' => ['nullable', 'array', 'max:50'],
            'content.references.*.key' => ['nullable', 'string', 'max:30'],
            'content.references.*.name' => ['nullable', 'string', 'max:150'],
            'content.references.*.content' => ['nullable', 'string', 'max:10000'],
            'content.references.*.phase' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E', 'F'])],
            'content.references.*.element' => ['nullable', 'string', 'max:100'],
            'content.chapters' => ['nullable', 'array', 'max:100'],
            'content.chapters.*.id' => ['nullable', 'string', 'max:64'],
            'content.chapters.*.title' => ['nullable', 'string', 'max:255'],
            'content.chapters.*.semester' => ['nullable', Rule::in(['1', '2', 'odd', 'even'])],
            'content.units' => ['nullable', 'array', 'max:500'],
            'content.units.*.id' => ['nullable', 'string', 'max:64'],
            'content.units.*.chapter_id' => ['nullable', 'string', 'max:64'],
            'content.units.*.code' => ['nullable', 'string', 'max:50'],
            'content.units.*.objective' => ['nullable', 'string', 'max:10000'],
            'content.units.*.material' => ['nullable', 'string', 'max:10000'],
            'content.units.*.jp' => ['nullable', 'integer', 'min:0', 'max:100'],
            'content.units.*.kkm' => ['nullable', 'integer', 'min:0', 'max:100'],
            'content.waktu' => ['nullable', 'array'],
            'content.waktu.jp_per_minggu' => ['nullable', 'integer', 'min:1', 'max:40'],
            'content.waktu.minggu_efektif' => ['nullable', 'array'],
            'content.waktu.minggu_efektif.1' => ['nullable', 'integer', 'min:0', 'max:30'],
            'content.waktu.minggu_efektif.2' => ['nullable', 'integer', 'min:0', 'max:30'],
        ];
    }

    private function normalizeData(array $data, array $existing = []): array
    {
        return array_merge($existing, [
            'references' => array_values(Arr::get($data, 'references', [])),
            'chapters' => array_values(Arr::get($data, 'chapters', [])),
            'units' => array_values(Arr::get($data, 'units', [])),
            'waktu' => Arr::get($data, 'waktu', $existing['waktu'] ?? [
                'jp_per_minggu' => 2,
                'minggu_efektif' => ['1' => 18, '2' => 18],
            ]),
        ]);
    }
}

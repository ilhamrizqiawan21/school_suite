<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\JournalService;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class JournalController extends Controller
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'history_date' => ['nullable', 'date'],
            'class_group_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'journal_status' => ['nullable', 'in:all,pending,completed'],
            'history_search' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'in:latest,oldest'],
            'focus_session_id' => ['nullable', 'integer'],
        ]);

        $activeTerm = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();
        $teacherId = Teacher::where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->value('id');
        $agendaDate = Carbon::parse($filters['date'] ?? now()->toDateString());
        if ($teacherId && $activeTerm) {
            $this->materializeScheduledSessions($schoolId, (int) $teacherId, $activeTerm, $agendaDate);
        }
        $classGroupId = $filters['class_group_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;
        $journalStatus = $filters['journal_status'] ?? 'all';

        $baseSessionQuery = LessonSession::query()
            ->where('school_id', $schoolId)
            ->where('term_id', $activeTerm?->id ?? 0)
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when(! $teacherId, fn ($query) => $query->whereKey(0));

        $assignments = TeachingAssignment::query()
            ->where('teacher_id', $teacherId ?? 0)
            ->where('term_id', $activeTerm?->id ?? 0)
            ->with(['classGroup:id,name,grade_level', 'subject:id,name,code'])
            ->get(['id', 'class_group_id', 'subject_id', 'term_id'])
            ->map(fn (TeachingAssignment $assignment) => [
                'id' => $assignment->id,
                'class_group' => ['id' => $assignment->classGroup?->id, 'name' => $assignment->classGroup?->name ?? '—'],
                'subject' => ['id' => $assignment->subject?->id, 'name' => $assignment->subject?->name ?? '—', 'code' => $assignment->subject?->code ?? '—'],
            ])
            ->values();
        $classIds = $assignments->pluck('class_group.id')->filter();
        $subjectIds = $assignments->pluck('subject.id')->filter();
        $classes = ClassGroup::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $classIds)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);
        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $relations = ['journal', 'attendanceRecords:id,lesson_session_id,status', 'classGroup:id,name', 'subject:id,name,code'];
        $agendaSessions = (clone $baseSessionQuery)
            ->with($relations)
            ->whereDate('date', $agendaDate->toDateString())
            ->orderBy('start_time')
            ->get()
            ->map(fn (LessonSession $session) => $this->serializeSession($session))
            ->values();

        $journalQuery = (clone $baseSessionQuery)
            ->with($relations)
            ->when($filters['history_date'] ?? null, fn ($query, $date) => $query->whereDate('date', $date))
            ->when($classGroupId, fn ($query) => $query->where('class_group_id', $classGroupId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($journalStatus === 'pending', fn ($query) => $query->whereDoesntHave('journal'))
            ->when($journalStatus === 'completed', fn ($query) => $query->whereHas('journal'))
            ->when($filters['history_search'] ?? null, function ($query, string $search): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereHas('journal', fn ($journal) => $journal->where('topic', 'like', $term)->orWhere('theme', 'like', $term))
                        ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('classGroup', fn ($class) => $class->where('name', 'like', $term));
                });
            });
        $sortOrder = $filters['sort_order'] ?? 'latest';
        $journalQuery->orderBy('date', $sortOrder === 'oldest' ? 'asc' : 'desc')
            ->orderBy('start_time', $sortOrder === 'oldest' ? 'asc' : 'desc');
        $journalSessions = $journalQuery->paginate(15)->withQueryString();
        $journalSessions->setCollection(
            $journalSessions->getCollection()->map(fn (LessonSession $session) => $this->serializeSession($session))
        );

        $allSessions = (clone $baseSessionQuery)
            ->with($relations)
            ->get();
        [$recapSummary, $monthlyRecap] = $this->buildRecap($allSessions, $activeTerm);

        return Inertia::render('Academic/Journals/Index', [
            'draft_storage_scope' => hash('sha256', $schoolId.':'.$user->id),
            'active_term' => $activeTerm,
            'agenda_date' => $agendaDate->toDateString(),
            'focus_session_id' => isset($filters['focus_session_id']) ? (int) $filters['focus_session_id'] : null,
            'agenda_sessions' => $agendaSessions,
            'assignments' => $assignments,
            'classes' => $classes,
            'subjects' => $subjects,
            'journal_sessions' => $journalSessions,
            'filters' => [
                'date' => $agendaDate->toDateString(),
                'history_date' => $filters['history_date'] ?? null,
                'class_group_id' => $classGroupId,
                'subject_id' => $subjectId,
                'journal_status' => $journalStatus,
                'history_search' => $filters['history_search'] ?? '',
                'sort_order' => $sortOrder,
            ],
            'recap' => [
                'summary' => $recapSummary,
                'months' => $monthlyRecap,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lesson_session_id' => ['required', 'integer', 'exists:lesson_sessions,id'],
            'topic' => ['required', 'string', 'max:255'],
            'learning_objectives' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'theme' => ['nullable', 'string', 'max:255'],
            'period_count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'opening_activity' => ['nullable', 'string', 'max:10000'],
            'main_activity' => ['nullable', 'string', 'max:10000'],
            'closing_activity' => ['nullable', 'string', 'max:10000'],
            'follow_up' => ['nullable', 'string', 'max:10000'],
            'attendance_notes' => ['nullable', 'string', 'max:10000'],
            'observations' => ['nullable', 'string', 'max:10000'],
            'reflection' => ['nullable', 'string', 'max:10000'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->firstOrFail();
        $teacherId = Teacher::where('school_id', $schoolId)
            ->where('user_id', $request->user()->id)
            ->value('id');

        abort_if(! $teacherId, 403);

        $session = LessonSession::query()
            ->where('school_id', $schoolId)
            ->where('term_id', $activeTerm->id)
            ->where('teacher_id', $teacherId)
            ->findOrFail((int) $validated['lesson_session_id']);

        $this->journalService->saveJournal(
            lessonSessionId: $session->id,
            topic: $validated['topic'],
            learningObjectives: $validated['learning_objectives'] ?? null,
            notes: $validated['notes'] ?? null,
            theme: $validated['theme'] ?? null,
            periodCount: (int) ($validated['period_count'] ?? 2),
            openingActivity: $validated['opening_activity'] ?? null,
            mainActivity: $validated['main_activity'] ?? null,
            closingActivity: $validated['closing_activity'] ?? null,
            followUp: $validated['follow_up'] ?? null,
            attendanceNotes: $validated['attendance_notes'] ?? null,
            observations: $validated['observations'] ?? null,
            reflection: $validated['reflection'] ?? null,
            actor: $request->user()
        );

        return back()->with('message', 'Jurnal pembelajaran berhasil disimpan.');
    }

    public function createSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_assignment_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $activeTerm = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->firstOrFail();
        $teacherId = Teacher::where('school_id', $schoolId)->where('user_id', $user->id)->value('id');

        abort_if(! $teacherId, 403);

        $assignment = TeachingAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('term_id', $activeTerm->id)
            ->whereHas('classGroup', fn ($query) => $query->where('school_id', $schoolId))
            ->whereHas('subject', fn ($query) => $query->where('school_id', $schoolId))
            ->findOrFail((int) $validated['teaching_assignment_id']);

        $sessionDate = Carbon::parse($validated['date'])->startOfDay();
        abort_unless(
            ! $sessionDate->lt($activeTerm->start_date->copy()->startOfDay())
                && ! $sessionDate->gt($activeTerm->end_date->copy()->endOfDay()),
            422,
            'Tanggal harus berada di dalam periode akademik aktif.'
        );

        $meetingNumber = (int) LessonSession::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->max('meeting_number') + 1;

        $session = LessonSession::create([
            'school_id' => $schoolId,
            'teaching_assignment_id' => $assignment->id,
            'term_id' => $activeTerm->id,
            'class_group_id' => $assignment->class_group_id,
            'subject_id' => $assignment->subject_id,
            'teacher_id' => $teacherId,
            'date' => $sessionDate->toDateString(),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'meeting_number' => $meetingNumber,
            'status' => 'scheduled',
        ]);

        return redirect()->route('academic.journals.index', [
            'date' => $session->date->toDateString(),
            'focus_session_id' => $session->id,
        ])
            ->with('message', 'Sesi pertemuan berhasil dibuat. Jurnal dapat diisi dari agenda.');
    }

    private function materializeScheduledSessions(int $schoolId, int $teacherId, Term $term, Carbon $date): void
    {
        if ($date->lt($term->start_date->copy()->startOfDay()) || $date->gt($term->end_date->copy()->endOfDay())) {
            return;
        }

        $schedules = Schedule::query()
            ->where('school_id', $schoolId)
            ->where('term_id', $term->id)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $date->isoWeekday())
            ->where('status', 'published')
            ->orderBy('start_time')
            ->get();

        $assignments = TeachingAssignment::query()
            ->where('term_id', $term->id)
            ->where('teacher_id', $teacherId)
            ->whereIn('class_group_id', $schedules->pluck('class_group_id')->unique())
            ->whereIn('subject_id', $schedules->pluck('subject_id')->unique())
            ->get()
            ->keyBy(fn (TeachingAssignment $assignment): string => $assignment->class_group_id.':'.$assignment->subject_id);
        $nextMeetings = [];

        foreach ($schedules as $schedule) {
            $assignment = $assignments->get($schedule->class_group_id.':'.$schedule->subject_id);
            if (! $assignment) {
                continue;
            }

            $nextMeetings[$assignment->id] ??= (int) LessonSession::query()
                ->where('teaching_assignment_id', $assignment->id)
                ->max('meeting_number') + 1;

            $session = LessonSession::firstOrCreate(
                ['schedule_id' => $schedule->id, 'date' => $date->toDateString()],
                [
                    'school_id' => $schoolId,
                    'teaching_assignment_id' => $assignment->id,
                    'term_id' => $term->id,
                    'class_group_id' => $schedule->class_group_id,
                    'subject_id' => $schedule->subject_id,
                    'teacher_id' => $teacherId,
                    'date' => $date->toDateString(),
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'meeting_number' => $nextMeetings[$assignment->id],
                    'status' => 'scheduled',
                ]
            );

            if ($session->wasRecentlyCreated) {
                $nextMeetings[$assignment->id]++;
            }
        }
    }

    public function exportWord(Request $request, int $lessonSessionId)
    {
        $session = $this->authorizedSession($request, $lessonSessionId);
        abort_unless($session->journal, 404);

        $school = $request->user()->school;
        abort_unless($school, 404);
        $journal = $session->journal;
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);
        $section->addText('JURNAL MENGAJAR', ['bold' => true, 'size' => 15], ['alignment' => 'center', 'spaceAfter' => 4]);
        $section->addText('JURNAL HARIAN GURU', ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 18]);

        $identity = $section->addTable(['borderSize' => 0, 'cellMargin' => 30]);
        foreach ([
            ['Sekolah', $school->name],
            ['Guru', $session->teacher?->name ?? $request->user()->name],
            ['Mata pelajaran', $session->subject?->name ?? '—'],
            ['Kelas / semester', ($session->classGroup?->name ?? '—').' / '.($session->term?->name ?? '—')],
            ['Pertemuan', 'Ke-'.$session->meeting_number.' · '.Carbon::parse($session->date)->locale('id')->translatedFormat('l, d F Y')],
        ] as [$label, $value]) {
            $identity->addRow();
            $identity->addCell(2200)->addText($label, ['bold' => true]);
            $identity->addCell(200)->addText(':');
            $identity->addCell(7000)->addText((string) $value);
        }
        $section->addTextBreak(1);

        $tableStyle = ['borderSize' => 6, 'borderColor' => 'AAB4C3', 'cellMargin' => 90];
        $labelStyle = ['bgColor' => 'EEF2F7', 'valign' => 'center'];
        foreach ([
            ['Alokasi waktu', $journal->period_count.' JP'],
            ['Materi pokok', $journal->topic],
            ['Tujuan pembelajaran', $journal->learning_objectives],
            ['Topik / tema', $journal->theme],
            ['Kegiatan pendahuluan', $journal->opening_activity],
            ['Kegiatan inti', $journal->main_activity],
            ['Kegiatan penutup', $journal->closing_activity],
            ['Tindak lanjut', $journal->follow_up],
            ['Catatan tambahan', $journal->notes],
        ] as [$label, $value]) {
            if ($label === 'Topik / tema' && blank($value)) continue;
            $table = $section->addTable($tableStyle);
            $table->addRow();
            $table->addCell(2600, $labelStyle)->addText($label, ['bold' => true]);
            $table->addCell(7400)->addText((string) ($value ?: '—'));
        }

        $section->addTextBreak(1);
        $section->addText('KEHADIRAN', ['bold' => true, 'size' => 11], ['spaceAfter' => 6]);
        $summary = $this->attendanceSummary($session);
        $studentCount = Enrollment::query()
            ->where('term_id', $session->term_id)
            ->where('class_group_id', $session->class_group_id)
            ->count();
        $attendance = $section->addTable($tableStyle);
        $attendance->addRow();
        foreach (['Jumlah siswa' => 'total', 'Hadir' => 'present', 'Sakit' => 'sick', 'Izin' => 'permitted', 'Alpa' => 'absent'] as $label => $key) {
            $attendance->addCell(2000, $labelStyle)->addText($label, ['bold' => true]);
            $attendance->addCell(1200)->addText((string) ($key === 'total' ? $studentCount : ($summary[$key] ?? 0)));
        }

        foreach ([
            ['Keterangan presensi', $journal->attendance_notes],
            ['Hasil pengamatan', $journal->observations],
            ['Refleksi guru', $journal->reflection],
        ] as [$label, $value]) {
            $table = $section->addTable($tableStyle);
            $table->addRow();
            $table->addCell(2600, $labelStyle)->addText($label, ['bold' => true]);
            $table->addCell(7400)->addText((string) ($value ?: '—'));
        }

        $section->addTextBreak(2);
        $signatures = $section->addTable(['borderSize' => 0, 'cellMargin' => 60]);
        $signatures->addRow();
        $signatures->addCell(5000)->addText('Kepala Sekolah,', [], ['alignment' => 'center']);
        $signatures->addCell(5000)->addText('Guru Mata Pelajaran,', [], ['alignment' => 'center']);
        $signatures->addRow();
        $signatures->addCell(5000)->addText("\n\n\n".($school->principal_name ?: '________________________'), ['bold' => true], ['alignment' => 'center']);
        $signatures->addCell(5000)->addText("\n\n\n".($session->teacher?->name ?? $request->user()->name), ['bold' => true], ['alignment' => 'center']);

        $filename = $this->journalFilename($session, 'docx');

        return response()->streamDownload(
            fn () => \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    public function destroy(Request $request, int $lessonSessionId): RedirectResponse
    {
        $session = $this->authorizedSession($request, $lessonSessionId);
        $this->journalService->deleteJournal($session, $request->user());

        return back()->with('message', 'Jurnal dihapus. Sesi kembali ke status belum diisi.');
    }

    public function exportPdf(Request $request, int $lessonSessionId)
    {
        $session = $this->authorizedSession($request, $lessonSessionId);
        abort_unless($session->journal, 404);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('academic.journal-pdf', [
            'session' => $session,
            'journal' => $session->journal,
            'attendance' => $this->attendanceSummary($session),
            'studentCount' => Enrollment::query()->where('term_id', $session->term_id)->where('class_group_id', $session->class_group_id)->count(),
            'school' => $request->user()->school,
            'teacherName' => $session->teacher?->name ?? $request->user()->name,
        ])->setPaper('a4', 'portrait')->download($this->journalFilename($session, 'pdf'));
    }

    private function authorizedSession(Request $request, int $lessonSessionId): LessonSession
    {
        $schoolId = (int) $request->user()->school_id;
        $teacherId = Teacher::where('school_id', $schoolId)->where('user_id', $request->user()->id)->value('id');
        abort_if(! $teacherId, 403);

        return LessonSession::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->with(['journal', 'attendanceRecords:id,lesson_session_id,status', 'teacher:id,name', 'subject:id,name', 'classGroup:id,name', 'term:id,name'])
            ->findOrFail($lessonSessionId);
    }

    /** @return array{present: int, sick: int, permitted: int, absent: int} */
    private function attendanceSummary(LessonSession $session): array
    {
        $summary = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];
        foreach ($session->attendanceRecords as $record) {
            if (array_key_exists($record->status, $summary)) {
                $summary[$record->status]++;
            }
        }

        return $summary;
    }

    private function journalFilename(LessonSession $session, string $extension): string
    {
        $name = implode('_', ['Jurnal', $session->subject?->name ?? 'Mapel', $session->classGroup?->name ?? 'Kelas', 'Pert-'.$session->meeting_number, $session->date?->format('Y-m-d')]);
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name) ?: 'Jurnal_Mengajar';

        return $name.'.'.$extension;
    }

    /**
     * @return array{0: array<string, int|float>, 1: array<int, array<string, int|float|string>>}
     */
    private function buildRecap(Collection $sessions, ?Term $activeTerm): array
    {
        $summary = [
            'total_sessions' => $sessions->count(),
            'completed_journals' => $sessions->filter(fn (LessonSession $session) => $session->journal !== null)->count(),
            'pending_journals' => $sessions->filter(fn (LessonSession $session) => $session->journal === null)->count(),
            'completion_rate' => 0.0,
            'present' => 0,
            'sick' => 0,
            'permitted' => 0,
            'absent' => 0,
        ];

        $attendance = $sessions->flatMap(fn (LessonSession $session) => $session->attendanceRecords);
        foreach (['present', 'sick', 'permitted', 'absent'] as $status) {
            $summary[$status] = $attendance->where('status', $status)->count();
        }
        if ($summary['total_sessions'] > 0) {
            $summary['completion_rate'] = round(($summary['completed_journals'] / $summary['total_sessions']) * 100, 1);
        }

        if (! $activeTerm) {
            return [$summary, []];
        }

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $month = $activeTerm->start_date->copy()->startOfMonth();
        $lastMonth = $activeTerm->end_date->copy()->startOfMonth();
        $months = [];

        while ($month->lte($lastMonth)) {
            $monthKey = $month->format('Y-m');
            $monthSessions = $sessions->filter(
                fn (LessonSession $session) => $session->date?->format('Y-m') === $monthKey
            );
            $totalSessions = $monthSessions->count();
            $completedJournals = $monthSessions->filter(fn (LessonSession $session) => $session->journal !== null)->count();
            $monthAttendance = $monthSessions->flatMap(fn (LessonSession $session) => $session->attendanceRecords);

            $months[] = [
                'month_key' => $monthKey,
                'label' => $monthLabels[(int) $month->format('n') - 1],
                'total_sessions' => $totalSessions,
                'completed_journals' => $completedJournals,
                'pending_journals' => $totalSessions - $completedJournals,
                'completion_rate' => $totalSessions > 0 ? round(($completedJournals / $totalSessions) * 100, 1) : 0.0,
                'present' => $monthAttendance->where('status', 'present')->count(),
                'sick' => $monthAttendance->where('status', 'sick')->count(),
                'permitted' => $monthAttendance->where('status', 'permitted')->count(),
                'absent' => $monthAttendance->where('status', 'absent')->count(),
            ];
            $month->addMonth();
        }

        return [$summary, $months];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSession(LessonSession $session): array
    {
        $attendance = [
            'present' => 0,
            'sick' => 0,
            'permitted' => 0,
            'absent' => 0,
        ];
        foreach ($session->attendanceRecords as $record) {
            if (array_key_exists($record->status, $attendance)) {
                $attendance[$record->status]++;
            }
        }

        return [
            'id' => $session->id,
            'teaching_assignment_id' => $session->teaching_assignment_id,
            'date' => $session->date?->toDateString(),
            'start_time' => substr((string) $session->start_time, 0, 5),
            'end_time' => substr((string) $session->end_time, 0, 5),
            'meeting_number' => $session->meeting_number,
            'status' => $session->status,
            'journal_status' => $session->journal ? 'completed' : 'pending',
            'journal' => $session->journal ? [
                'id' => $session->journal->id,
                'topic' => $session->journal->topic,
                'theme' => $session->journal->theme,
                'period_count' => $session->journal->period_count,
                'learning_objectives' => $session->journal->learning_objectives,
                'notes' => $session->journal->notes,
                'opening_activity' => $session->journal->opening_activity,
                'main_activity' => $session->journal->main_activity,
                'closing_activity' => $session->journal->closing_activity,
                'follow_up' => $session->journal->follow_up,
                'attendance_notes' => $session->journal->attendance_notes,
                'observations' => $session->journal->observations,
                'reflection' => $session->journal->reflection,
                'created_at' => $session->journal->created_at?->toIso8601String(),
                'updated_at' => $session->journal->updated_at?->toIso8601String(),
            ] : null,
            'attendance_summary' => $attendance,
            'class_group' => [
                'id' => $session->classGroup?->id,
                'name' => $session->classGroup?->name ?? '—',
            ],
            'subject' => [
                'id' => $session->subject?->id,
                'name' => $session->subject?->name ?? '—',
                'code' => $session->subject?->code ?? '—',
            ],
        ];
    }
}

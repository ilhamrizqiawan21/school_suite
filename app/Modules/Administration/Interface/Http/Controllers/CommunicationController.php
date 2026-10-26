<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Administration\Application\Services\CommunicationService;
use App\Modules\Administration\Infrastructure\Models\AcademicCalendarEvent;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Administration\Infrastructure\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunicationController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'operator', 'principal', 'teacher', 'homeroom'];

    public function __construct(
        private readonly CommunicationService $communication,
        private readonly AcademicQueryService $academicQuery,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);
        $classIds = $this->visibleClassIds($request);

        $announcements = Announcement::query()
            ->with(['author:id,name', 'classGroup:id,name'])
            ->where('school_id', $schoolId)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('audience_role')->orWhere('audience_role', $user->role?->name))
            ->where(fn ($query) => $query->whereNull('class_group_id')->orWhereIn('class_group_id', $classIds))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->latest('published_at')
            ->get();

        $events = AcademicCalendarEvent::query()
            ->with(['creator:id,name', 'classGroup:id,name'])
            ->where('school_id', $schoolId)
            ->where(fn ($query) => $query->whereNull('audience_role')->orWhere('audience_role', $user->role?->name))
            ->where(fn ($query) => $query->whereNull('class_group_id')->orWhereIn('class_group_id', $classIds))
            ->where('starts_at', '>=', now()->subMonth())
            ->orderBy('starts_at')
            ->get();

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        $classes = $activeTerm
            ? $this->academicQuery->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();
        if ($user->hasRole('student')) {
            $classes = $classes->whereIn('id', $classIds)->values();
        }

        return Inertia::render('Communication/Index', [
            'announcements' => $announcements,
            'events' => $events,
            'notifications' => $notifications,
            'classes' => $classes,
            'active_tab' => $request->query('tab', 'announcements'),
            'can_manage' => $user->hasRole(self::MANAGER_ROLES),
            'can_chat' => $user->hasRole(['teacher', 'homeroom', 'student']),
            'can_whatsapp' => $user->hasRole(['admin', 'operator', 'principal', 'homeroom']),
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'audience_role' => ['nullable', Rule::in(['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student', 'parent'])],
            'class_group_id' => ['nullable', 'integer', Rule::exists('class_groups', 'id')->where('school_id', $request->user()->school_id)],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        unset($validated['attachment']);
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $validated['attachment_path'] = $file->storeAs('communication/announcements', Str::uuid().'.'.$file->extension(), 'local');
            $validated['attachment_name'] = $file->getClientOriginalName();
        }

        $this->communication->publishAnnouncement($request->user(), $validated);

        return back()->with('message', 'Pengumuman berhasil diterbitkan dan notifikasi telah dibuat.');
    }

    public function destroyAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOwnedManager($request, $announcement->school_id);
        if ($announcement->attachment_path) {
            Storage::disk('local')->delete($announcement->attachment_path);
        }
        $announcement->delete();

        return back()->with('message', 'Pengumuman berhasil dihapus.');
    }

    public function downloadAttachment(Request $request, Announcement $announcement): StreamedResponse
    {
        $user = $request->user();
        $classIds = $this->visibleClassIds($request);
        abort_unless(
            (int) $announcement->school_id === (int) $user->school_id
            && $announcement->attachment_path
            && (! $announcement->audience_role || $announcement->audience_role === $user->role?->name)
            && (! $announcement->class_group_id || in_array((int) $announcement->class_group_id, $classIds, true))
            && (! $announcement->expires_at || $announcement->expires_at->isFuture()),
            404
        );

        return Storage::disk('local')->download($announcement->attachment_path, $announcement->attachment_name);
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        UserNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('message', 'Semua notifikasi ditandai telah dibaca.');
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_type' => ['required', Rule::in(['academic', 'exam', 'holiday', 'meeting', 'activity'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'audience_role' => ['nullable', Rule::in(['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student', 'parent'])],
            'class_group_id' => ['nullable', 'integer', Rule::exists('class_groups', 'id')->where('school_id', $request->user()->school_id)],
        ]);

        AcademicCalendarEvent::create([
            ...$validated,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('message', 'Agenda kalender berhasil ditambahkan.');
    }

    public function destroyEvent(Request $request, AcademicCalendarEvent $event): RedirectResponse
    {
        $this->authorizeOwnedManager($request, $event->school_id);
        $event->delete();

        return back()->with('message', 'Agenda kalender berhasil dihapus.');
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(self::MANAGER_ROLES), 403);
    }

    private function authorizeOwnedManager(Request $request, int $schoolId): void
    {
        $this->authorizeManager($request);
        abort_unless((int) $request->user()->school_id === $schoolId, 404);
    }

    /** @return list<int> */
    private function visibleClassIds(Request $request): array
    {
        $user = $request->user();
        if (! $user->hasRole('student')) {
            $activeTerm = $this->academicQuery->getActiveTerm((int) $user->school_id);

            return $activeTerm
                ? $this->academicQuery->getClassGroups((int) $user->school_id, $activeTerm->academic_year_id)
                    ->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];
        }

        $schoolId = (int) $user->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);

        return $this->academicQuery->getStudentClassGroupIds(
            (int) $user->id,
            $activeTerm?->id,
            $schoolId
        );
    }
}

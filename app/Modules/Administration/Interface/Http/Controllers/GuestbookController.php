<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Administration\Application\Services\GuestbookService;
use App\Modules\Administration\Infrastructure\Models\GuestbookVisit;
use App\Modules\Core\Application\Services\SchoolScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GuestbookController extends Controller
{
    public function __construct(
        private readonly GuestbookService $guestbook,
        private readonly AcademicQueryService $academicQuery,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    /**
     * Public self-registration kiosk/form for visitors.
     * Privacy invariant (M4-TU-06): NEVER exposes any list of visitors or guest records publicly.
     */
    public function publicCreate(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        return Inertia::render('Public/Services/Guestbook', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'categories' => collect(GuestbookService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'hosts' => $this->academicQuery->getPublicDirectoryTeachers($schoolId),
            'confirmationSlip' => $request->session()->get('guestbook_confirmation_slip'),
        ]);
    }

    public function publicStore(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'visitor_organization' => ['nullable', 'string', 'max:180'],
            'visitor_phone' => ['required', 'string', 'max:30'],
            'visitor_email' => ['nullable', 'email', 'max:150'],
            'category' => ['required', Rule::in(array_keys(GuestbookService::CATEGORIES))],
            'purpose' => ['required', 'string', 'max:255'],
            'host_teacher_id' => ['nullable', 'integer'],
            'host_name' => ['nullable', 'string', 'max:150'],
            'host_unit' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $visit = $this->guestbook->recordVisit($schoolId, $validated, null);

        return redirect()->route('administration.guestbook.public.create')
            ->with('message', 'Registrasi kunjungan buku tamu berhasil dicatat.')
            ->with('guestbook_confirmation_slip', [
                'visit_code' => $visit->visit_code,
                'visitor_name' => $visit->visitor_name,
                'host_name' => $visit->host_name,
                'checked_in_at' => $visit->checked_in_at?->toIso8601String(),
            ]);
    }

    /**
     * Receptionist & TU internal workspace (M4-TU-06).
     */
    public function index(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $filters = [
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'category' => $request->query('category', ''),
            'status' => $request->query('status', ''),
            'search' => $request->query('search', ''),
        ];

        $data = $this->guestbook->listForStaff($schoolId, $filters);

        return Inertia::render('Administration/Guestbook/Index', [
            'visits' => $data['visits'],
            'pagination' => $data['pagination'],
            'summary' => $data['summary'],
            'filters' => $filters,
            'categories' => collect(GuestbookService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'hosts' => $this->academicQuery->getPublicDirectoryTeachers($schoolId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'visitor_organization' => ['nullable', 'string', 'max:180'],
            'visitor_phone' => ['nullable', 'string', 'max:30'],
            'visitor_email' => ['nullable', 'email', 'max:150'],
            'category' => ['required', Rule::in(array_keys(GuestbookService::CATEGORIES))],
            'purpose' => ['required', 'string', 'max:255'],
            'host_teacher_id' => ['nullable', 'integer'],
            'host_name' => ['nullable', 'string', 'max:150'],
            'host_unit' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->guestbook->recordVisit($schoolId, $validated, $user);

        return back()->with('message', 'Kunjungan tamu berhasil dicatat oleh resepsionis.');
    }

    public function checkOut(Request $request, GuestbookVisit $visit): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->guestbook->checkOut((int) $user->school_id, $visit, $user, $validated['notes'] ?? null);

        return back()->with('message', 'Waktu pulang pengunjung berhasil dicatat.');
    }

    public function export(Request $request): HttpResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $filters = [
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'category' => $request->query('category', ''),
            'status' => $request->query('status', ''),
        ];

        $csv = $this->guestbook->exportCsv($schoolId, $filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap-buku-tamu.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

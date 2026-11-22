<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Administration\Application\Services\GuestbookService;
use App\Modules\Administration\Infrastructure\Models\GuestbookVisit;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GuestbookController extends Controller
{
    public function __construct(
        private readonly GuestbookService $guestbook,
        private readonly AcademicQueryService $academicQuery,
        private readonly SchoolScopeService $schoolScope,
        private readonly PrivateStorageService $storage,
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
                'service_hours' => $school['service_hours'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'contentBlocks' => $school['content_blocks'] ?? [],
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
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg', 'max:512', 'dimensions:max_width=1280,max_height=1280'],
        ]);

        $storedPhoto = null;
        try {
            $visit = DB::transaction(function () use ($schoolId, $validated, $request, &$storedPhoto): GuestbookVisit {
                $visit = $this->guestbook->recordVisit($schoolId, $validated, null);
                if ($request->hasFile('photo')) {
                    $storedPhoto = $this->storage->storeValidatedUpload(
                        $request->file('photo'),
                        "schools/{$schoolId}/guestbook/{$visit->id}",
                        ['image/jpeg'],
                        524_288,
                    );
                }

                return $visit;
            });
        } catch (Throwable $exception) {
            if ($storedPhoto !== null) {
                $this->storage->delete($storedPhoto['path']);
            }

            if ($exception instanceof InvalidArgumentException) {
                throw ValidationException::withMessages(['photo' => $exception->getMessage()]);
            }

            throw $exception;
        }

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
        $data['visits'] = array_map(static function (array $visit) use ($schoolId): array {
            $photos = Storage::disk('local')->files("schools/{$schoolId}/guestbook/{$visit['id']}");
            $visit['photo_url'] = $photos !== []
                ? route('administration.guestbook.admin.photo', $visit['id'])
                : null;

            return $visit;
        }, $data['visits']);

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

    public function photo(Request $request, GuestbookVisit $visit): StreamedResponse
    {
        $schoolId = (int) $request->user()->school_id;
        abort_unless((int) $visit->school_id === $schoolId, 404);
        $photos = Storage::disk('local')->files("schools/{$schoolId}/guestbook/{$visit->id}");
        abort_if($photos === [], 404);

        return Storage::disk('local')->response($photos[0], null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
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

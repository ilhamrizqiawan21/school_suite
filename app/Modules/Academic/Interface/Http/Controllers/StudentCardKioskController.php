<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Academic\Application\Services\StudentCardKioskService;
use App\Modules\Academic\Infrastructure\Models\StudentCard;
use App\Modules\Core\Application\Services\SchoolScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentCardKioskController extends Controller
{
    public function __construct(
        private readonly StudentCardKioskService $kioskService,
        private readonly AcademicQueryService $academicQuery,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function cardsIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Academic/Attendance/Cards', [
            'cards' => $this->kioskService->listCards($schoolId, $search),
            'students' => $this->academicQuery->searchActiveStudentsForStaff($schoolId, $search, 50),
            'devices' => $this->kioskService->listDevices($schoolId),
            'filters' => ['search' => $search],
        ]);
    }

    public function issueCard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'card_uid' => ['nullable', 'string', 'max:80'],
            'qr_token' => ['nullable', 'string', 'max:80'],
        ]);

        $this->kioskService->issueCard(
            $request->user(),
            (int) $validated['student_id'],
            $validated['card_uid'] ?? null,
            $validated['qr_token'] ?? null,
        );

        return back()->with('message', 'Kartu RFID/QR siswa berhasil didaftarkan.');
    }

    public function revokeCard(Request $request, StudentCard $card): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->kioskService->revokeCard($request->user(), $card, $validated['reason']);

        return back()->with('message', 'Kartu siswa berhasil dicabut.');
    }

    public function registerDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:150'],
            'gate_enabled' => ['nullable', 'boolean'],
        ]);

        $this->kioskService->registerDevice(
            $request->user(),
            $validated['name'],
            $validated['location'] ?? null,
            (bool) ($validated['gate_enabled'] ?? false),
        );

        return back()->with('message', 'Perangkat kios presensi berhasil didaftarkan.');
    }

    public function kioskIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $school = $this->schoolScope->getSchoolDetails($schoolId);

        return Inertia::render('Academic/Attendance/Kiosk', [
            'school' => $school,
            'devices' => $this->kioskService->listDevices($schoolId),
            'recentScans' => $this->kioskService->listRecentScans($schoolId),
            'operations' => $this->schoolScope->getOperationsSettings($schoolId),
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
            'scan_method' => ['nullable', 'in:rfid,qr,manual'],
            'device_id' => ['nullable', 'integer'],
            'client_event_id' => ['nullable', 'string', 'max:80'],
            'scanned_at' => ['nullable', 'date'],
        ]);

        $result = $this->kioskService->processScan(
            schoolId: $schoolId,
            rawCode: $validated['code'],
            scanMethod: (string) ($validated['scan_method'] ?? 'rfid'),
            deviceId: isset($validated['device_id']) ? (int) $validated['device_id'] : null,
            clientEventId: $validated['client_event_id'] ?? null,
            actor: $user,
            scannedAtIso: $validated['scanned_at'] ?? null,
        );

        $statusCode = $result['status'] === 'rejected' ? 422 : 200;

        return response()->json($result, $statusCode);
    }

    public function syncBatch(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $validated = $request->validate([
            'device_id' => ['nullable', 'integer'],
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.code' => ['required', 'string', 'max:120'],
            'events.*.scan_method' => ['nullable', 'in:rfid,qr,manual'],
            'events.*.client_event_id' => ['required', 'string', 'max:80'],
            'events.*.scanned_at' => ['nullable', 'date'],
        ]);

        $results = $this->kioskService->syncOfflineBatch(
            schoolId: $schoolId,
            events: $validated['events'],
            deviceId: isset($validated['device_id']) ? (int) $validated['device_id'] : null,
            actor: $user,
        );

        return response()->json([
            'synced_count' => count($results),
            'results' => $results,
        ]);
    }

    public function triggerGate(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'simulated_ready',
            'message' => 'Sinyal kontrol Gate diterima dalam mode simulasi (menunggu kontrak perangkat K-04).',
        ]);
    }
}

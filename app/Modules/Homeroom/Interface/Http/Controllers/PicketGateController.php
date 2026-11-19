<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Homeroom\Application\Services\PicketGateService;
use App\Modules\Homeroom\Infrastructure\Models\GatePass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PicketGateController extends Controller
{
    public function __construct(
        private readonly PicketGateService $picketGate,
        private readonly AcademicQueryService $academicQuery,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function picketIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $statusFilter = trim((string) $request->query('status', ''));

        return Inertia::render('Homeroom/Picket/Index', [
            'passes' => $this->picketGate->listPasses($schoolId, $statusFilter),
            'students' => $this->academicQuery->searchActiveStudentsForStaff($schoolId, '', 50),
            'filters' => ['status' => $statusFilter],
            'timezone' => $this->schoolScope->getTimezone($schoolId),
        ]);
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $q = trim((string) $request->query('q', ''));

        return response()->json([
            'students' => $this->academicQuery->searchActiveStudentsForStaff($schoolId, $q, 15),
        ]);
    }

    public function issuePass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'exit_type' => ['required', Rule::in(['temporary_out', 'early_home', 'school_duty'])],
            'reason' => ['required', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:180'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
        ]);

        $pass = $this->picketGate->issuePass($request->user(), $validated);

        return back()->with('message', "Izin keluar berhasil diterbitkan ({$pass->pass_code}).");
    }

    public function cancelPass(Request $request, GatePass $gatePass): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->picketGate->cancelPass($request->user(), $gatePass, $validated['reason']);

        return back()->with('message', 'Izin keluar berhasil dibatalkan.');
    }

    public function postIndex(Request $request): Response
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $unlocked = $request->session()->get("security_post_unlocked.{$schoolId}") === true;
        $ops = $this->schoolScope->getOperationsSettings($schoolId);

        return Inertia::render('Homeroom/SecurityPost/Index', [
            'unlocked' => $unlocked,
            'hasPin' => $ops['has_security_post_pin'],
            'canConfigurePin' => $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value]),
            'passes' => $unlocked ? $this->picketGate->listPasses($schoolId) : [],
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'pin' => ['required', 'string', 'max:20'],
        ]);

        if (! $this->schoolScope->verifySecurityPostPin($schoolId, $validated['pin'])) {
            return back()->withErrors([
                'pin' => 'Kode akses pos jaga tidak valid.',
            ]);
        }

        $request->session()->put("security_post_unlocked.{$schoolId}", true);

        return back()->with('message', 'Akses panel pos jaga dibuka.');
    }

    public function lock(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $request->session()->forget("security_post_unlocked.{$schoolId}");

        return back()->with('message', 'Panel pos jaga dikunci.');
    }

    public function configurePin(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value]), 403);

        $validated = $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        $this->schoolScope->setSecurityPostPin((int) $user->school_id, $validated['pin']);

        return back()->with('message', 'Kode PIN pos jaga berhasil diperbarui.');
    }

    public function scanPass(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        if ($request->session()->get("security_post_unlocked.{$schoolId}") !== true) {
            return response()->json([
                'status' => 'rejected',
                'code' => 'post_locked',
                'message' => 'Panel pos jaga terkunci. Masukkan PIN akses pos terlebih dahulu.',
            ], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'mode' => ['nullable', Rule::in(['auto', 'exit', 'return'])],
            'handover_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->picketGate->processGateCheck(
            actor: $user,
            rawCode: $validated['code'],
            mode: (string) ($validated['mode'] ?? 'auto'),
            handoverNotes: $validated['handover_notes'] ?? null,
        );

        return response()->json($result, $result['status'] === 'rejected' ? 422 : 200);
    }
}

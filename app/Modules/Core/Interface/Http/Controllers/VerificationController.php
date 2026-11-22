<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
        private readonly AcademicQueryService $academicQuery,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $token = trim((string) ($request->query('code') ?: $request->query('kode', '')));
        $docResult = $token !== '' ? $this->documentService->verifyPublicDocument($schoolId, $token) : null;

        return Inertia::render('Public/Verification/Index', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'mode' => trim((string) $request->query('mode', 'document')),
            'initialCode' => $token,
            'documentResult' => $docResult,
            'studentResult' => null,
        ]);
    }

    public function verify(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $mode = (string) $request->input('mode', 'document');

        $docResult = null;
        $studentResult = null;

        if ($mode === 'student') {
            $validated = $request->validate([
                'nisn' => ['required', 'string', 'max:30'],
                'name' => ['required', 'string', 'min:3', 'max:120'],
            ]);
            $studentResult = $this->academicQuery->verifyPublicStudentStatus($schoolId, $validated['nisn'], $validated['name']);
        } else {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:100'],
            ]);
            $docResult = $this->documentService->verifyPublicDocument($schoolId, $validated['code']);
        }

        return Inertia::render('Public/Verification/Index', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'mode' => $mode,
            'initialCode' => (string) $request->input('code', ''),
            'documentResult' => $docResult,
            'studentResult' => $studentResult,
        ]);
    }

    public function studentData(): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $schoolModel = School::query()->find($schoolId);
        $hideDemo = (bool) ($schoolModel?->publicContentRegistry()['published']['shell.options']['hide_demo_fallbacks'] ?? false);

        return Inertia::render('Public/Students/Index', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'summary' => $this->academicQuery->publicStudentSummary($schoolId),
            'showDemoDirectory' => app()->isLocal() && ! $hideDemo,
            'studentResult' => null,
        ]);
    }

    public function checkStudentData(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $schoolModel = School::query()->find($schoolId);
        $hideDemo = (bool) ($schoolModel?->publicContentRegistry()['published']['shell.options']['hide_demo_fallbacks'] ?? false);

        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        return Inertia::render('Public/Students/Index', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'summary' => $this->academicQuery->publicStudentSummary($schoolId),
            'showDemoDirectory' => app()->isLocal() && ! $hideDemo,
            'studentResult' => $this->academicQuery->verifyPublicStudentStatus($schoolId, $validated['nisn'], $validated['name']),
        ]);
    }
}

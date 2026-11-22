<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExternalApplicantAuthController extends Controller
{
    private const ALLOWED_RETURN_PATHS = [
        '/layanan/ptsp',
        '/layanan/tracer-alumni',
        '/alumni',
        '/layanan/legalisir',
        '/layanan/mutasi',
        '/ppdb',
    ];

    public function __construct(
        private readonly ExternalApplicantAuthService $applicantAuth,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function show(Request $request): Response
    {
        $school = School::query()->firstOrFail();
        $requestedReturn = trim((string) $request->query('return_to', ''));
        $safeReturnPath = in_array($requestedReturn, self::ALLOWED_RETURN_PATHS, true) ? $requestedReturn : null;
        $returnToTracer = $safeReturnPath === '/layanan/tracer-alumni';
        $request->session()->put('tracer_alumni_return', $returnToTracer);
        $request->session()->put('external_applicant_return_to', $safeReturnPath);

        return Inertia::render('Public/Services/PtspAccount', [
            'school' => $this->schoolPayload($school),
            'applicant' => $this->applicantAuth->currentFromSession($request, (int) $school->id),
            'applicantTypes' => [
                ['value' => 'general', 'label' => 'Masyarakat / Umum'],
                ['value' => 'guardian', 'label' => 'Orang Tua / Wali Siswa'],
                ['value' => 'student', 'label' => 'Siswa'],
                ['value' => 'alumni', 'label' => 'Alumni'],
                ['value' => 'external_agency', 'label' => 'Instansi / Mitra'],
            ],
            'recoveryStatus' => session('recovery_status'),
            'returnToTracer' => $returnToTracer,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'applicant_type' => ['required', Rule::in(ExternalApplicantAuthService::ALLOWED_TYPES)],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'organization_name' => ['nullable', 'string', 'max:180'],
            'raw_identifier' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $applicant = $this->applicantAuth->register((int) $school->id, $validated);
        $this->applicantAuth->bindSession($request, (int) $school->id, (int) $applicant['id']);

        return $this->redirectAfterApplicantAuth($request, $applicant, 'Akun pemohon PTSP berhasil dibuat dan Anda telah masuk.');
    }

    public function login(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $applicant = $this->applicantAuth->attemptLogin(
            (int) $school->id,
            $validated['email'],
            $validated['password'],
        );

        if ($applicant === null) {
            return back()->withErrors([
                'email' => 'Email atau kata sandi akun pemohon tidak sesuai.',
            ])->onlyInput('email');
        }

        $this->applicantAuth->bindSession($request, (int) $school->id, (int) $applicant['id']);

        return $this->redirectAfterApplicantAuth($request, $applicant, 'Berhasil masuk ke akun pemohon PTSP.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->applicantAuth->clearSession($request);

        return redirect()->route('core.ptsp.account.show')
            ->with('message', 'Anda telah keluar dari akun pemohon.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();
        $current = $this->applicantAuth->currentFromSession($request, (int) $school->id);
        abort_unless($current !== null, 401);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'organization_name' => ['nullable', 'string', 'max:180'],
            'applicant_type' => ['required', Rule::in(ExternalApplicantAuthService::ALLOWED_TYPES)],
        ]);

        $this->applicantAuth->updateProfile((int) $school->id, (int) $current['id'], $validated);

        return back()->with('message', 'Identitas dan kontak pemohon berhasil diperbarui.');
    }

    public function requestRecovery(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'verification_hint' => ['required', 'string', 'min:4', 'max:30'],
        ]);

        $result = $this->applicantAuth->requestRecovery(
            (int) $school->id,
            $validated['email'],
            $validated['verification_hint'],
        );

        if (! $result['accepted']) {
            return back()->withErrors([
                'verification_hint' => 'Email atau 4 digit terakhir kontak/identitas tidak cocok.',
            ])->onlyInput('email');
        }

        return back()->with('recovery_status', 'Permintaan pemulihan akun diterima. Periksa kanal notifikasi terdaftar untuk kode pemulihan.');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'recovery_token' => ['required', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $reset = $this->applicantAuth->resetPassword(
            (int) $school->id,
            $validated['email'],
            $validated['recovery_token'],
            $validated['password'],
        );

        if (! $reset) {
            return back()->withErrors([
                'recovery_token' => 'Kode pemulihan tidak valid atau sudah kedaluwarsa.',
            ])->onlyInput('email');
        }

        return redirect()->route('core.ptsp.account.show')
            ->with('message', 'Kata sandi akun pemohon berhasil diperbarui. Silakan masuk.');
    }

    /**
     * @param  array<string, mixed>  $applicant
     */
    private function redirectAfterApplicantAuth(Request $request, array $applicant, string $message): RedirectResponse
    {
        $returnToTracer = $request->session()->pull('tracer_alumni_return', false) && ($applicant['applicant_type'] ?? null) === 'alumni';
        $safeReturnPath = $request->session()->pull('external_applicant_return_to');

        if ($returnToTracer) {
            return redirect()->route('academic.alumni.tracer.portal')->with('message', $message);
        }

        if (is_string($safeReturnPath) && in_array($safeReturnPath, self::ALLOWED_RETURN_PATHS, true)) {
            return redirect()->to($safeReturnPath)->with('message', $message);
        }

        return redirect()->route('administration.ptsp.public.index')->with('message', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolPayload(School $school): array
    {
        $snapshot = $this->schoolScope->getPrimaryPublicSnapshot();

        return [
            'name' => $school->name,
            'logo_url' => $school->publicBrandingUrl('logo'),
            'theme' => $snapshot['theme'] ?? $school->publicTheme(),
        ];
    }
}

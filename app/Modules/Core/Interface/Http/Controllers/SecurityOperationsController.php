<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\BlockedIp;
use App\Modules\Core\Infrastructure\Models\LoginAttemptLog;
use App\Modules\Core\Infrastructure\Models\SystemErrorEvent;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityOperationsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeViewer($request);
        $schoolId = (int) $request->user()->school_id;

        return Inertia::render('Core/Security/Index', [
            'blocked_ips' => BlockedIp::with('blocker:id,name')->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->latest()->get(),
            'login_logs' => LoginAttemptLog::with('user:id,name')->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->latest()->limit(100)->get(),
            'errors' => SystemErrorEvent::with('resolver:id,name')->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->latest('last_seen_at')->limit(100)->get(),
            'can_manage' => $request->user()->hasRole('admin'),
        ]);
    }

    public function block(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['ip_address' => ['required', 'ip', 'max:45', 'unique:security_blocked_ips,ip_address'], 'reason' => ['required', 'string', 'max:500'], 'expires_at' => ['nullable', 'date', 'after:now']]);
        abort_if($data['ip_address'] === $request->ip(), 422, 'IP yang sedang digunakan tidak dapat diblokir.');
        $item = BlockedIp::create([...$data, 'school_id' => $request->user()->school_id, 'blocked_by' => $request->user()->id, 'is_active' => true]);
        $this->audit->log('security.ip.blocked', 'BlockedIp', (string) $item->id, ['ip_address' => $item->ip_address, 'reason' => $item->reason], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        return back()->with('message', 'Alamat IP berhasil diblokir.');
    }

    public function unblock(Request $request, BlockedIp $blockedIp): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($blockedIp->school_id === null || (int) $blockedIp->school_id === (int) $request->user()->school_id, 404);
        $blockedIp->update(['is_active' => false]);
        $this->audit->log('security.ip.unblocked', 'BlockedIp', (string) $blockedIp->id, ['is_active' => false], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        return back()->with('message', 'Blokir IP dinonaktifkan.');
    }

    public function resolveError(Request $request, SystemErrorEvent $error): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($error->school_id === null || (int) $error->school_id === (int) $request->user()->school_id, 404);
        $error->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return back()->with('message', 'Error ditandai selesai.');
    }

    private function authorizeViewer(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'principal']), 403);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}

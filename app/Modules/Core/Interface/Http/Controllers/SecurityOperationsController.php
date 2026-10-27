<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use App\Modules\Core\Infrastructure\Models\BlockedIp;
use App\Modules\Core\Infrastructure\Models\LoginAttemptLog;
use App\Modules\Core\Infrastructure\Models\SystemErrorEvent;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SecurityOperationsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeViewer($request);
        $schoolId = (int) $request->user()->school_id;
        $isAdmin = $request->user()->hasRole('admin');

        $auditPages = $isAdmin
            ? AuditEvent::query()
                ->with('actor:id,name')
                ->where('school_id', $schoolId)
                ->latest('created_at')
                ->latest('id')
                ->paginate(15, ['id', 'actor_id', 'event_type', 'target_type', 'target_id', 'created_at'], 'audit_page')->withQueryString()
            : null;
        $auditEvents = $auditPages
            ? $auditPages->getCollection()->map(fn (AuditEvent $event): array => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'target_type' => $event->target_type,
                'target_id' => $event->target_id,
                'actor_name' => $event->actor?->name,
                'created_at' => $event->created_at,
            ])
                ->values()
            : collect();

        $ips = BlockedIp::with('blocker:id,name')->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->latest('id')->paginate(15, ['*'], 'ip_page')->withQueryString();
        $logins = LoginAttemptLog::with('user:id,name')->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->latest('id')->paginate(15, ['id', 'school_id', 'user_id', 'status', 'reason', 'ip_address', 'created_at'], 'login_page')->withQueryString();
        $errors = SystemErrorEvent::with('resolver:id,name')->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->latest('last_seen_at')->paginate(15, ['*'], 'error_page')->withQueryString();
        $pagination = collect(['ips' => $ips, 'login' => $logins, 'errors' => $errors, 'audit' => $auditPages])->map(fn ($pages) => $pages ? ['current' => $pages->currentPage(), 'last' => $pages->lastPage(), 'total' => $pages->total(), 'previous' => $pages->previousPageUrl(), 'next' => $pages->nextPageUrl()] : null);

        return Inertia::render('Core/Security/Index', [
            'blocked_ips' => $ips->items(),
            'login_logs' => $logins->items(),
            'errors' => $errors->items(),
            'pagination' => $pagination,
            'audit_events' => $auditEvents,
            'can_manage' => $isAdmin,
        ]);
    }

    public function block(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['ip_address' => ['required', 'ip', 'max:45'], 'reason' => ['required', 'string', 'max:500'], 'expires_at' => ['nullable', 'date', 'after:now']]);
        $existing = BlockedIp::query()->where('school_id', $request->user()->school_id)->where('ip_address', $data['ip_address'])->first();
        $reusable = $existing && (! $existing->is_active || $existing->expires_at?->isPast()) ? $existing : null;
        $request->validate(['ip_address' => [Rule::unique('security_blocked_ips', 'ip_address')->ignore($reusable?->id)]]);
        if ($data['ip_address'] === $request->ip()) {
            throw ValidationException::withMessages(['ip_address' => 'IP yang sedang digunakan tidak dapat diblokir.']);
        }
        $data['expires_at'] ??= null;
        $item = $reusable ?? new BlockedIp;
        $item->fill([...$data, 'school_id' => $request->user()->school_id, 'blocked_by' => $request->user()->id, 'is_active' => true])->save();
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

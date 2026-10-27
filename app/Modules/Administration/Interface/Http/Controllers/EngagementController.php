<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Administration\Infrastructure\Models\ClassChatMessage;
use App\Modules\Administration\Infrastructure\Models\ClassChatRoom;
use App\Modules\Administration\Infrastructure\Models\WhatsappMessageLog;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EngagementController extends Controller
{
    public function __construct(private readonly AcademicQueryService $academicQuery, private readonly AuditLogger $audit) {}

    public function chat(Request $request): Response
    {
        abort_unless($request->user()->hasRole(['teacher', 'homeroom', 'student']), 403);
        $schoolId = (int) $request->user()->school_id;
        $term = $this->academicQuery->getActiveTerm($schoolId);
        $assignmentIds = $term ? $this->visibleAssignmentIds($request, $term->id) : [];
        $assignments = $term ? $this->academicQuery->getLearningTeachingAssignments($term->id, $request->user()->hasRole(['teacher', 'homeroom']) ? (int) $request->user()->id : null)->whereIn('id', $assignmentIds)->values() : collect();
        $rooms = ClassChatRoom::where('school_id', $schoolId)->whereIn('teaching_assignment_id', $assignmentIds)->orderBy('name')->get();
        $roomId = (int) ($request->query('room_id') ?: $rooms->first()?->id);
        $room = $rooms->firstWhere('id', $roomId);
        $messages = $room ? ClassChatMessage::with('user:id,name')->where('class_chat_room_id', $room->id)->latest()->limit(100)->get()->reverse()->values() : collect();

        return Inertia::render('Communication/Chat', ['assignments' => $assignments, 'rooms' => $rooms, 'selected_room' => $room, 'messages' => $messages, 'can_manage' => $request->user()->hasRole(['teacher', 'homeroom'])]);
    }

    public function createRoom(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(['teacher', 'homeroom']), 403);
        $data = $request->validate(['teaching_assignment_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:180']]);
        $term = $this->academicQuery->getActiveTerm((int) $request->user()->school_id);
        abort_unless($term && in_array((int) $data['teaching_assignment_id'], $this->visibleAssignmentIds($request, $term->id), true), 403);
        $room = ClassChatRoom::firstOrCreate(['school_id' => $request->user()->school_id, 'teaching_assignment_id' => $data['teaching_assignment_id']], ['name' => $data['name'], 'is_active' => true]);

        $chatPath = $request->is('guru/*') ? '/guru/chat' : '/komunikasi/chat';

        return redirect($chatPath.'?room_id='.$room->id)->with('message', 'Ruang chat tersedia.');
    }

    public function sendMessage(Request $request, ClassChatRoom $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);
        abort_unless($room->is_active, 422);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $room->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return back();
    }

    public function moderate(Request $request, ClassChatMessage $message): RedirectResponse
    {
        $message->load('room');
        $this->authorizeRoom($request, $message->room);
        abort_unless($request->user()->hasRole(['teacher', 'homeroom']), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $message->update(['body' => '[Pesan dimoderasi]', 'moderated_by' => $request->user()->id, 'moderated_at' => now(), 'moderation_reason' => $data['reason']]);
        $this->audit->log('communication.chat.moderated', 'class_chat_message', (string) $message->id, ['reason' => $data['reason']], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        return back();
    }

    public function whatsapp(Request $request): Response
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'homeroom']), 403);
        $logs = WhatsappMessageLog::where('school_id', $request->user()->school_id)->latest('prepared_at')->limit(100)->get();

        return Inertia::render('Communication/Whatsapp', ['logs' => $logs, 'templates' => ['attendance' => 'Yth. Orang Tua/Wali {name}, kami menginformasikan kehadiran {student}: {detail}.', 'announcement' => 'Yth. {name}, {detail}', 'reminder' => 'Yth. {name}, pengingat untuk {detail}.']]);
    }

    public function prepareWhatsapp(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'homeroom']), 403);
        $data = $request->validate(['recipient_name' => ['required', 'string', 'max:180'], 'phone' => ['required', 'string', 'max:30'], 'template_key' => ['required', 'in:attendance,announcement,reminder'], 'student' => ['nullable', 'string', 'max:180'], 'detail' => ['required', 'string', 'max:1000']]);
        $phone = preg_replace('/\D+/', '', $data['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        } abort_unless(preg_match('/^62[0-9]{8,13}$/', $phone), 422, 'Nomor WhatsApp tidak valid.');
        $templates = ['attendance' => 'Yth. Orang Tua/Wali {name}, kami menginformasikan kehadiran {student}: {detail}.', 'announcement' => 'Yth. {name}, {detail}', 'reminder' => 'Yth. {name}, pengingat untuk {detail}.'];
        $message = strtr($templates[$data['template_key']], ['{name}' => $data['recipient_name'], '{student}' => $data['student'] ?: 'siswa', '{detail}' => $data['detail']]);
        WhatsappMessageLog::create(['school_id' => $request->user()->school_id, 'recipient_name' => $data['recipient_name'], 'phone' => $phone, 'template_key' => $data['template_key'], 'message' => $message, 'status' => 'prepared', 'prepared_by' => $request->user()->id, 'prepared_at' => now()]);

        return back()->with('message', 'Pesan WhatsApp disiapkan. Tidak ada pesan yang dikirim otomatis.');
    }

    public function markWhatsappSent(Request $request, WhatsappMessageLog $log): RedirectResponse
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'homeroom']) && (int) $log->school_id === (int) $request->user()->school_id, 403);
        $log->update(['status' => 'sent_manually', 'sent_at' => now()]);
        $this->audit->log('communication.whatsapp.marked_sent', 'whatsapp_message_log', (string) $log->id, ['status' => 'sent_manually'], actor: $request->user(), schoolId: (int) $log->school_id);

        return back()->with('message', 'Status pengiriman manual dicatat.');
    }

    private function authorizeRoom(Request $request, ClassChatRoom $room): void
    {
        $term = $this->academicQuery->getActiveTerm((int) $request->user()->school_id);
        abort_unless((int) $room->school_id === (int) $request->user()->school_id && $term && in_array((int) $room->teaching_assignment_id, $this->visibleAssignmentIds($request, $term->id), true), 403);
    }

    /** @return list<int> */
    private function visibleAssignmentIds(Request $request, int $termId): array
    {
        $user = $request->user();
        $query = DB::table('teaching_assignments')->join('class_groups', 'class_groups.id', '=', 'teaching_assignments.class_group_id')->where('teaching_assignments.term_id', $termId)->where('class_groups.school_id', $user->school_id);
        if ($user->hasRole(['teacher', 'homeroom'])) {
            $query->join('teachers', 'teachers.id', '=', 'teaching_assignments.teacher_id')->where('teachers.user_id', $user->id);
        } elseif ($user->hasRole('student')) {
            $query->join('enrollments', fn ($j) => $j->on('enrollments.class_group_id', '=', 'teaching_assignments.class_group_id')->where('enrollments.term_id', $termId))->join('students', 'students.id', '=', 'enrollments.student_id')->where('students.user_id', $user->id)->where('enrollments.status', 'active');
        } elseif (! $user->hasRole(['admin', 'operator', 'principal'])) {
            return [];
        }

        return $query->distinct()->pluck('teaching_assignments.id')->map(fn ($id) => (int) $id)->all();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdministrationCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $operator;

    private User $teacher;

    private User $principal;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Route::middleware('web')->group(app_path('Modules/Administration/Interface/Routes/correspondence.php'));
        Route::getRoutes()->refreshNameLookups();

        $this->school = School::create(['name' => 'Madrasah Persuratan', 'npsn' => '77553311', 'level' => 'MTs']);
        $roles = collect(['admin', 'operator', 'teacher', 'principal'])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name, 'label' => ucfirst($name), 'is_system' => true]),
        ]);
        $this->admin = $this->makeUser($roles['admin'], 'admin@persuratan.test', 'Admin Persuratan');
        $this->operator = $this->makeUser($roles['operator'], 'operator@persuratan.test', 'Operator Persuratan');
        $this->teacher = $this->makeUser($roles['teacher'], 'teacher@persuratan.test', 'Guru Persuratan');
        $this->principal = $this->makeUser($roles['principal'], 'principal@persuratan.test', 'Kepala Sekolah');
    }

    public function test_admin_and_operator_can_view_correspondence_but_teacher_cannot(): void
    {
        $this->actingAs($this->admin)
            ->get(route('administration.correspondence.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Administration/Correspondence/Index')
                ->has('records', 0));

        $this->actingAs($this->operator)->get(route('administration.correspondence.index'))->assertOk();
        $this->actingAs($this->teacher)->get(route('administration.correspondence.index'))->assertForbidden();
    }

    public function test_incoming_letter_gets_school_scoped_agenda_number_audit_and_private_attachment(): void
    {
        $response = $this->actingAs($this->operator)->post(route('administration.correspondence.store'), [
            'letter_type' => 'incoming',
            'letter_number' => 'EXT/12/2026',
            'letter_date' => '2026-10-20',
            'received_at' => '2026-10-22',
            'sender' => 'Kantor Wilayah',
            'subject' => 'Undangan rapat',
            'summary' => 'Rapat koordinasi sekolah.',
            'attachment' => UploadedFile::fake()->create('undangan.pdf', 20, 'application/pdf'),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->assertSame($this->school->id, $record->school_id);
        $this->assertSame($this->operator->id, $record->created_by);
        $this->assertSame('received', $record->status);
        $this->assertSame('IN/2026/000001', $record->agenda_number);
        Storage::disk('local')->assertExists($record->attachment_path);
        $this->assertStringStartsWith('correspondence/'.$this->school->id.'/', $record->attachment_path);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'actor_id' => $this->operator->id,
            'event_type' => 'administration.correspondence.created',
            'target_id' => (string) $record->id,
        ]);

        $this->actingAs($this->operator)
            ->get(route('administration.correspondence.attachment', $record->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_outgoing_letter_requires_user_entered_number_and_supports_sent_then_archive_flow(): void
    {
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Pengantar dokumen',
        ])->assertSessionHasErrors('letter_number');

        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing',
            'letter_number' => '001/ADM/2026',
            'letter_date' => '2026-10-22',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Pengantar dokumen',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $record = CorrespondenceRecord::firstOrFail();
        $this->assertSame('draft', $record->status);
        $this->actingAs($this->admin)
            ->patch(route('administration.correspondence.status', $record), ['status' => 'archived'])
            ->assertSessionHasErrors('status');

        $this->patch(route('administration.correspondence.status', $record), ['status' => 'sent'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('administration.correspondence.status', $record->fresh()), ['status' => 'archived'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('archived', $record->fresh()->status);
        $this->assertNotNull($record->fresh()->archived_at);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'event_type' => 'administration.correspondence.status_changed',
            'target_id' => (string) $record->id,
        ]);
        $this->get(route('administration.correspondence.index', ['filter' => 'archived']))
            ->assertInertia(fn ($page) => $page->has('records', 1)->where('records.0.status', 'archived'));
    }

    public function test_incoming_letter_requires_disposition_before_advancing_and_rejects_invalid_attachment(): void
    {
        $this->actingAs($this->operator)->post(route('administration.correspondence.store'), [
            'letter_type' => 'incoming',
            'received_at' => '2026-10-22',
            'sender' => 'Pengirim',
            'subject' => 'Surat dengan lampiran tidak valid',
            'attachment' => UploadedFile::fake()->create('script.php', 5, 'application/x-php'),
        ])->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('correspondence_records', 0);

        $this->actingAs($this->operator)->post(route('administration.correspondence.store'), [
            'letter_type' => 'incoming',
            'received_at' => '2026-10-22',
            'sender' => 'Pengirim',
            'subject' => 'Surat masuk',
        ])->assertRedirect();
        $record = CorrespondenceRecord::firstOrFail();

        $this->patch(route('administration.correspondence.status', $record), ['status' => 'disposition'])
            ->assertSessionHasErrors('disposition');
        $this->patch(route('administration.correspondence.status', $record), [
            'status' => 'disposition',
            'disposition' => 'Tindak lanjuti oleh bagian kurikulum.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('administration.correspondence.status', $record->fresh()), ['status' => 'done'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('done', $record->fresh()->status);
        $this->assertSame('Tindak lanjuti oleh bagian kurikulum.', $record->fresh()->disposition);
    }

    public function test_cross_school_records_and_attachments_are_not_visible_or_mutable(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '77553312', 'level' => 'MTs']);
        $record = CorrespondenceRecord::create([
            'school_id' => $otherSchool->id,
            'created_by' => $this->admin->id,
            'letter_type' => 'incoming',
            'agenda_number' => 'IN/2026/000999',
            'received_at' => '2026-10-22',
            'sender' => 'Sekolah Lain',
            'subject' => 'Rahasia sekolah lain',
            'status' => 'received',
            'attachment_path' => 'correspondence/999/secret.pdf',
            'attachment_name' => 'secret.pdf',
        ]);
        Storage::disk('local')->put($record->attachment_path, 'PRIVATE');

        $this->actingAs($this->admin)
            ->get(route('administration.correspondence.index'))
            ->assertInertia(fn ($page) => $page->has('records', 0));
        $this->patch(route('administration.correspondence.status', $record), ['status' => 'disposition'])
            ->assertNotFound();
        $this->get(route('administration.correspondence.attachment', $record->id))
            ->assertNotFound();
    }

    public function test_document_draft_can_be_edited_and_published_with_private_verification(): void
    {
        $data = [
            'letter_type' => 'outgoing', 'letter_number' => '12/ADM/2026', 'letter_date' => '2026-10-27',
            'recipient' => 'Rahasia penerima', 'subject' => 'Perihal rahasia', 'letter_body' => 'Isi rahasia',
            'signer_name' => 'Kepala Sekolah', 'signer_title' => 'Kepala Sekolah', 'attachment_note' => 'Satu berkas',
        ];
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->assertNull($record->verification_token);
        $this->get(route('administration.correspondence.pdf', $record))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $data['letter_body'] = 'Isi revisi rahasia';
        $this->patch(route('administration.correspondence.draft', $record), $data)->assertSessionHasNoErrors();
        $this->patch(route('administration.correspondence.status', $record), ['status' => 'sent'])->assertSessionHasErrors('status');
        $this->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertNotNull($record->verification_token);
        $this->assertSame($this->school->name, $record->school_snapshot['name']);
        $this->get(route('administration.correspondence.pdf', $record))->assertOk();
        $this->patch(route('administration.correspondence.draft', $record), $data)->assertSessionHasErrors('status');
        $this->assertSame('Isi revisi rahasia', $record->fresh()->letter_body);
        auth()->logout();
        $this->get(route('administration.correspondence.verify', $record->verification_token))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('12/ADM/2026')
            ->assertDontSee('Isi revisi rahasia')->assertDontSee('Rahasia penerima')->assertDontSee('Perihal rahasia');
        $this->get(route('administration.correspondence.verify', str_repeat('a', 64)))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('administration.correspondence.status', $record), ['status' => 'sent'])->assertSessionHasNoErrors();
        $this->patch(route('administration.correspondence.status', $record), ['status' => 'archived'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('administration.correspondence.verify', $record->verification_token))->assertOk()->assertSee('Diarsipkan');
    }

    public function test_document_requires_date_and_signer_and_cannot_be_accessed_across_schools(): void
    {
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing', 'letter_number' => 'X', 'recipient' => 'Y', 'subject' => 'Z', 'letter_body' => 'Isi',
        ])->assertSessionHasErrors(['letter_date', 'signer_name', 'signer_title']);
        $other = School::create(['name' => 'Sekolah Lain', 'npsn' => '77553313', 'level' => 'MTs']);
        $record = CorrespondenceRecord::create([
            'school_id' => $other->id, 'created_by' => $this->admin->id, 'letter_type' => 'outgoing',
            'letter_number' => 'X', 'subject' => 'Z', 'letter_body' => 'Rahasia', 'status' => 'draft',
        ]);
        $this->get(route('administration.correspondence.pdf', $record))->assertNotFound();
        $this->patch(route('administration.correspondence.draft', $record), [])->assertNotFound();
    }

    public function test_principal_approval_is_required_when_requested_and_document_hash_is_published(): void
    {
        $data = [
            'letter_type' => 'outgoing', 'letter_number' => '15/ADM/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan', 'subject' => 'Surat resmi sekolah', 'letter_body' => 'Isi surat yang telah diperiksa.',
            'signer_name' => 'Kepala Sekolah', 'signer_title' => 'Kepala Sekolah', 'approval_required' => true,
            'signature_method' => 'principal_approval', 'letter_template' => 'official_school', 'urgency' => 'ordinary',
        ];
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->assertTrue($record->approval_required);
        $this->assertSame('pending', $record->approval_status);

        $this->actingAs($this->admin)->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])
            ->assertSessionHasErrors('status');
        $this->actingAs($this->principal)->get(route('administration.correspondence.index'))
            ->assertOk()->assertInertia(fn ($page) => $page->where('can_manage', false)->has('records', 1));
        $this->actingAs($this->principal)->patch(route('administration.correspondence.approve', $record), [
            'decision' => 'approve', 'note' => 'Format dan isi telah diperiksa.',
        ])->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertSame('approved', $record->approval_status);
        $this->assertNotNull($record->approved_at);

        $this->actingAs($this->admin)->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])
            ->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $record->document_hash);
        $this->assertArrayHasKey('legal_name', $record->school_snapshot);
        $this->actingAs($this->admin)->get(route('administration.correspondence.pdf', $record))->assertOk();
        auth()->logout();
        $this->get(route('administration.correspondence.verify', $record->verification_token))
            ->assertOk()->assertSee($record->document_hash);
    }

    public function test_material_draft_edit_after_approval_requires_reapproval_before_issue(): void
    {
        $data = [
            'letter_type' => 'outgoing', 'letter_number' => '16/ADM/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan', 'subject' => 'Surat yang perlu disetujui', 'letter_body' => 'Isi awal.',
            'signer_name' => 'Kepala Sekolah', 'signer_title' => 'Kepala Sekolah', 'approval_required' => true,
        ];
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();

        $this->actingAs($this->principal)->patch(route('administration.correspondence.approve', $record), [
            'decision' => 'approve', 'note' => 'Disetujui.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('approved', $record->fresh()->approval_status);

        $data['letter_body'] = 'Isi yang telah diubah.';
        unset($data['approval_required']);
        $this->actingAs($this->admin)->patch(route('administration.correspondence.draft', $record), $data)
            ->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('pending', $record->approval_status);
        $this->assertNull($record->approved_by);
        $this->assertNull($record->approved_at);
        $this->assertNull($record->approval_note);
        $this->actingAs($this->admin)->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])
            ->assertSessionHasErrors('status');
    }

    public function test_disabling_approval_clears_existing_approval_and_allows_issue(): void
    {
        $data = [
            'letter_type' => 'outgoing', 'letter_number' => '17/ADM/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan', 'subject' => 'Surat tanpa persetujuan', 'letter_body' => 'Isi surat.',
            'signer_name' => 'Kepala Sekolah', 'signer_title' => 'Kepala Sekolah', 'approval_required' => true,
        ];
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->actingAs($this->principal)->patch(route('administration.correspondence.approve', $record), [
            'decision' => 'approve', 'note' => 'Disetujui.',
        ])->assertSessionHasNoErrors();

        $data['approval_required'] = false;
        $this->actingAs($this->admin)->patch(route('administration.correspondence.draft', $record), $data)
            ->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertFalse($record->approval_required);
        $this->assertSame('not_required', $record->approval_status);
        $this->assertNull($record->approved_by);
        $this->assertNull($record->approved_at);
        $this->assertNull($record->approval_note);
        $this->actingAs($this->admin)->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])
            ->assertSessionHasNoErrors();
    }

    private function makeUser(Role $role, string $email, string $name): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => $name,
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }
}

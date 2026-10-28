<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Administration\Application\Contracts\ElectronicSignatureProvider;
use App\Modules\Administration\Application\DTO\ElectronicSignatureResult;
use App\Modules\Administration\Application\Jobs\ProcessCorrespondenceSignature;
use App\Modules\Administration\Application\Services\CorrespondenceService;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceDocumentVersion;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatory;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatureRequest;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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

    public function test_outgoing_letter_number_is_unique_per_school_and_enforced_by_database(): void
    {
        $data = [
            'letter_type' => 'outgoing',
            'letter_number' => 'UNIQ/001/2026',
            'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Surat unik',
        ];

        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)
            ->assertSessionHasErrors('letter_number');

        $nextYear = [...$data, 'letter_date' => '2027-01-10'];
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $nextYear)
            ->assertRedirect()->assertSessionHasNoErrors();

        $otherSchool = School::create(['name' => 'Sekolah Nomor Lain', 'npsn' => '77553314', 'level' => 'MTs']);
        $otherAdmin = $this->makeUser(Role::where('name', 'admin')->firstOrFail(), 'admin@nomor-lain.test', 'Admin Nomor Lain', $otherSchool);
        $this->actingAs($otherAdmin)->post(route('administration.correspondence.store'), $data)
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->expectException(QueryException::class);
        CorrespondenceRecord::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'letter_type' => 'outgoing',
            'letter_number' => $data['letter_number'],
            'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Bypass validasi',
            'status' => 'draft',
        ]);
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
        $otherAdmin = $this->makeUser(Role::where('name', 'admin')->firstOrFail(), 'admin@sekolah-lain.test', 'Admin Sekolah Lain', $otherSchool);
        $record = CorrespondenceRecord::create([
            'school_id' => $otherSchool->id,
            'created_by' => $otherAdmin->id,
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
        $this->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])->assertSessionHasErrors('status');
        $this->finalizeManual($record);
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

    public function test_verification_can_expire_or_be_revoked_and_each_attempt_is_audited_without_raw_token(): void
    {
        $record = $this->issueDocument();
        $token = $record->verification_token;

        $this->assertNotNull($record->verification_expires_at);
        $record->update(['verification_expires_at' => now()->subSecond()]);
        auth()->logout();
        $this->get(route('administration.correspondence.verify', $token))->assertNotFound();
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'administration.correspondence.verification_rejected',
            'target_id' => (string) $record->id,
        ]);

        $record->update(['verification_expires_at' => now()->addDay(), 'verification_revoked_at' => null]);
        $this->actingAs($this->admin)
            ->patch(route('administration.correspondence.verification.revoke', $record))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->verification_revoked_at);

        auth()->logout();
        $this->get(route('administration.correspondence.verify', $token))->assertNotFound();
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'administration.correspondence.verification_revoked',
            'target_id' => (string) $record->id,
        ]);

        foreach (AuditEvent::where('target_id', (string) $record->id)->get() as $event) {
            $this->assertStringNotContainsString($token, json_encode($event->toArray(), JSON_THROW_ON_ERROR));
        }
    }

    public function test_document_requires_date_and_signer_and_cannot_be_accessed_across_schools(): void
    {
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing', 'letter_number' => 'X', 'recipient' => 'Y', 'subject' => 'Z', 'letter_body' => 'Isi',
        ])->assertSessionHasErrors(['letter_date', 'signer_name', 'signer_title']);
        $other = School::create(['name' => 'Sekolah Lain', 'npsn' => '77553313', 'level' => 'MTs']);
        $otherAdmin = $this->makeUser(Role::where('name', 'admin')->firstOrFail(), 'admin@dokumen-lain.test', 'Admin Dokumen Lain', $other);
        $record = CorrespondenceRecord::create([
            'school_id' => $other->id, 'created_by' => $otherAdmin->id, 'letter_type' => 'outgoing',
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
        $this->actingAs($this->admin)->post(route('administration.correspondence.approval.submit', $record))->assertSessionHasNoErrors();
        $this->actingAs($this->principal)->patch(route('administration.correspondence.approve', $record), [
            'decision' => 'approve', 'note' => 'Format dan isi telah diperiksa.',
        ])->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertSame('approved', $record->approval_status);
        $this->assertNotNull($record->approved_at);

        $this->actingAs($this->admin)->patch(route('administration.correspondence.status', $record), ['status' => 'issued'])
            ->assertSessionHasErrors('status');
        $this->finalizeManual($record);
        $record->refresh();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $record->document_hash);
        $this->assertArrayHasKey('legal_name', $record->school_snapshot);
        $this->actingAs($this->admin)->get(route('administration.correspondence.pdf', $record))->assertOk();
        $pdfResponse = $this->actingAs($this->admin)->get(route('administration.correspondence.pdf', $record));
        $this->assertSame($record->document_hash, hash('sha256', $pdfResponse->getContent()));
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'administration.correspondence.pdf_rendered',
            'target_id' => (string) $record->id,
        ]);
        auth()->logout();
        $this->get(route('administration.correspondence.verify', $record->verification_token))
            ->assertOk()->assertSee($record->document_hash)->assertSee('Integritas: Valid');
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'administration.correspondence.verification_viewed',
            'target_id' => (string) $record->id,
        ]);
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

        $this->actingAs($this->admin)->post(route('administration.correspondence.approval.submit', $record))->assertSessionHasNoErrors();
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
        $this->actingAs($this->admin)->post(route('administration.correspondence.approval.submit', $record))->assertSessionHasNoErrors();
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
            ->assertSessionHasErrors('status');
        $this->finalizeManual($record);
    }

    public function test_creator_must_belong_to_the_record_school_at_database_boundary(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Pembuat Lain', 'npsn' => '77553315', 'level' => 'MTs']);
        $otherAdmin = $this->makeUser(Role::where('name', 'admin')->firstOrFail(), 'admin@pembuat-lain.test', 'Admin Pembuat Lain', $otherSchool);

        $this->expectException(QueryException::class);
        CorrespondenceRecord::create([
            'school_id' => $this->school->id,
            'created_by' => $otherAdmin->id,
            'letter_type' => 'incoming',
            'received_at' => '2026-10-28',
            'sender' => 'Pengirim',
            'subject' => 'Provenance creator',
            'status' => 'received',
        ]);
    }

    public function test_approver_must_belong_to_the_record_school_at_database_boundary(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Penyetuju Lain', 'npsn' => '77553316', 'level' => 'MTs']);
        $otherPrincipal = $this->makeUser(Role::where('name', 'principal')->firstOrFail(), 'principal@penyetuju-lain.test', 'Principal Penyetuju Lain', $otherSchool);
        $record = CorrespondenceRecord::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'letter_type' => 'outgoing',
            'letter_number' => 'PROV/001/2026',
            'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Provenance approver',
            'letter_body' => 'Isi surat.',
            'signer_name' => 'Kepala Sekolah',
            'signer_title' => 'Kepala Sekolah',
            'status' => 'draft',
            'approval_required' => true,
            'approval_status' => 'pending',
        ]);

        $this->expectException(QueryException::class);
        $record->update(['approved_by' => $otherPrincipal->id]);
    }

    public function test_signatory_profiles_are_school_scoped_and_manual_signed_pdf_is_immutable(): void
    {
        $this->actingAs($this->admin)->post(route('administration.correspondence.signatories.store'), [
            'name' => 'Drs. Kepala Sekolah', 'title' => 'Kepala Sekolah', 'employee_number' => '198001012006041001',
            'rank' => 'Pembina', 'template_scopes' => ['official_school'],
        ])->assertSessionHasNoErrors();
        $signatory = CorrespondenceSignatory::firstOrFail();

        $this->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing', 'letter_number' => 'MANUAL/001/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Orang Tua Siswa', 'recipient_address' => 'Di tempat', 'subject' => 'Undangan',
            'opening_greeting' => 'Dengan hormat,', 'letter_body' => 'Isi undangan.', 'closing_text' => 'Demikian disampaikan.',
            'carbon_copy' => 'Ketua Komite', 'signer_name' => $signatory->name, 'signer_title' => $signatory->title,
            'signatory_id' => $signatory->id, 'signature_method' => 'manual',
        ])->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();

        $this->post(route('administration.correspondence.manual.prepare', $record))->assertSessionHasNoErrors();
        $this->assertSame('awaiting_signature', $record->fresh()->status);
        $this->assertDatabaseHas('correspondence_document_versions', ['correspondence_record_id' => $record->id, 'kind' => 'unsigned']);

        $this->post(route('administration.correspondence.manual.upload', $record), [
            'signed_document' => UploadedFile::fake()->createWithContent('palsu.pdf', 'bukan pdf'),
        ])->assertSessionHasErrors('signed_document');

        $signedBytes = "%PDF-1.4\n% manual signed scan\n%%EOF";
        $this->post(route('administration.correspondence.manual.upload', $record), [
            'signed_document' => UploadedFile::fake()->createWithContent('surat-ditandatangani.pdf', $signedBytes),
        ])->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertSame('issued', $record->status);
        $this->assertSame(hash('sha256', $signedBytes), $record->document_hash);
        $final = CorrespondenceDocumentVersion::where('correspondence_record_id', $record->id)->where('kind', 'manual_scan')->firstOrFail();
        $this->assertSame($signedBytes, Storage::disk('local')->get($final->storage_path));
        $this->get(route('administration.correspondence.pdf', $record))->assertContent($signedBytes);

        Storage::disk('local')->put($final->storage_path, $signedBytes.'tampered');
        $this->assertSame('changed', app(CorrespondenceService::class)->verificationIntegrity($record));

        $otherSchool = School::create(['name' => 'Sekolah Penandatangan Lain', 'npsn' => '77553319', 'level' => 'MTs']);
        $otherAdmin = $this->makeUser(Role::where('name', 'admin')->firstOrFail(), 'admin@signer-lain.test', 'Admin Lain', $otherSchool);
        $this->actingAs($otherAdmin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing', 'letter_number' => 'LAIN/001', 'letter_date' => '2026-10-28',
            'recipient' => 'Tujuan', 'subject' => 'Lintas sekolah', 'letter_body' => 'Isi',
            'signer_name' => 'Nama', 'signer_title' => 'Jabatan', 'signatory_id' => $signatory->id,
        ])->assertSessionHasErrors('signatory_id');
    }

    public function test_unavailable_bsre_request_fails_safely_and_can_be_retried_idempotently(): void
    {
        Queue::fake();
        $signatory = CorrespondenceSignatory::create([
            'school_id' => $this->school->id, 'user_id' => $this->principal->id,
            'name' => $this->principal->name, 'title' => 'Kepala Sekolah', 'is_active' => true,
        ]);
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing', 'letter_number' => 'BSRE/001/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan', 'subject' => 'Permintaan TTE', 'letter_body' => 'Isi final.',
            'signer_name' => $signatory->name, 'signer_title' => $signatory->title,
            'signatory_id' => $signatory->id, 'signature_method' => 'bsre',
        ])->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->post(route('administration.correspondence.bsre.request', $record))->assertSessionHasNoErrors();
        Queue::assertPushed(ProcessCorrespondenceSignature::class, 1);
        $signatureRequest = CorrespondenceSignatureRequest::firstOrFail();
        $idempotencyKey = $signatureRequest->idempotency_key;

        try {
            app(CorrespondenceService::class)->processSignatureRequest($signatureRequest->id);
            $this->fail('Provider yang belum dikonfigurasi seharusnya gagal.');
        } catch (\RuntimeException) {
            $this->assertSame('failed', $signatureRequest->fresh()->status);
            $this->assertSame('signature_failed', $record->fresh()->status);
            $this->assertNull($record->fresh()->document_hash);
        }

        $this->post(route('administration.correspondence.bsre.retry', $signatureRequest))->assertSessionHasNoErrors();
        $signatureRequest->refresh();
        $this->assertSame('pending', $signatureRequest->status);
        $this->assertSame($idempotencyKey, $signatureRequest->idempotency_key);
        $this->assertSame(1, CorrespondenceSignatureRequest::count());
        Queue::assertPushed(ProcessCorrespondenceSignature::class, 2);
    }

    public function test_bsre_success_stores_provider_pdf_without_overlay_and_only_claims_verified_certificate_metadata(): void
    {
        Queue::fake();
        $signedBytes = "%PDF-1.4\n% provider signed bytes\n%%EOF";
        $this->app->bind(ElectronicSignatureProvider::class, fn () => new class($signedBytes) implements ElectronicSignatureProvider
        {
            public function __construct(private readonly string $bytes) {}

            public function sign(string $pdfBytes, CorrespondenceSignatory $signatory, string $idempotencyKey, array $context = []): ElectronicSignatureResult
            {
                return new ElectronicSignatureResult('provider-123', $this->bytes, ['verified' => true, 'subject' => 'CN=Kepala Sekolah']);
            }
        });
        $signatory = CorrespondenceSignatory::create([
            'school_id' => $this->school->id, 'user_id' => $this->principal->id,
            'name' => $this->principal->name, 'title' => 'Kepala Sekolah', 'is_active' => true,
        ]);
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing', 'letter_number' => 'BSRE/OK/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan', 'subject' => 'TTE berhasil', 'letter_body' => 'Isi final.',
            'signer_name' => $signatory->name, 'signer_title' => $signatory->title,
            'signatory_id' => $signatory->id, 'signature_method' => 'bsre',
        ])->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->post(route('administration.correspondence.bsre.request', $record))->assertSessionHasNoErrors();
        $request = CorrespondenceSignatureRequest::firstOrFail();

        app(CorrespondenceService::class)->processSignatureRequest($request->id);

        $record->refresh();
        $this->assertSame('issued', $record->status);
        $this->assertSame(hash('sha256', $signedBytes), $record->document_hash);
        $this->get(route('administration.correspondence.pdf', $record))->assertContent($signedBytes);
        auth()->logout();
        $this->get(route('administration.correspondence.verify', $record->verification_token))
            ->assertOk()->assertSee('Status sertifikat')->assertSee('Terverifikasi');
    }

    private function issueDocument(): CorrespondenceRecord
    {
        $data = [
            'letter_type' => 'outgoing', 'letter_number' => 'VERIFY/001/2026', 'letter_date' => '2026-10-28',
            'recipient' => 'Dinas Pendidikan', 'subject' => 'Surat verifikasi', 'letter_body' => 'Isi surat verifikasi.',
            'signer_name' => 'Kepala Sekolah', 'signer_title' => 'Kepala Sekolah',
        ];
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), $data)->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->finalizeManual($record);

        return $record->fresh();
    }

    private function makeUser(Role $role, string $email, string $name, ?School $school = null): User
    {
        return User::create([
            'school_id' => ($school ?? $this->school)->id,
            'role_id' => $role->id,
            'name' => $name,
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function finalizeManual(CorrespondenceRecord $record): void
    {
        $this->actingAs($this->admin)
            ->post(route('administration.correspondence.manual.prepare', $record))
            ->assertSessionHasNoErrors();
        $prepared = CorrespondenceDocumentVersion::where('correspondence_record_id', $record->id)
            ->where('kind', 'unsigned')->latest('version')->firstOrFail();
        $this->assertSame(Storage::disk('local')->get($prepared->storage_path), $this->get(route('administration.correspondence.pdf', $record->fresh()))->getContent());
        $this->actingAs($this->admin)
            ->post(route('administration.correspondence.manual.upload', $record), [
                'signed_document' => UploadedFile::fake()->createWithContent('hasil-tanda-tangan.pdf', "%PDF-1.4\n% signed\n%%EOF"),
            ])->assertSessionHasNoErrors();
    }
}

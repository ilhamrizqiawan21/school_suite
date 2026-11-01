<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Admissions\Infrastructure\Models\AdmissionPeriod;
use App\Modules\Admissions\Infrastructure\Models\AdmissionTrack;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use Database\Seeders\PilotSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdmissionsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotSchoolSeeder::class);
        $this->admin = User::where('email', 'admin@pilot.sch.id')->firstOrFail();
        $this->admin->forceFill(['must_change_password' => false])->save();
    }

    public function test_admin_can_configure_period_track_and_requirement(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admissions.periods.store'), [
                'name' => 'SPMB 2027/2028',
                'academic_year' => '2027/2028',
                'level' => 'SMP',
                'registration_start' => '2027-01-01',
                'registration_end' => '2027-03-01',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $period = AdmissionPeriod::firstOrFail();
        $this->assertSame('draft', $period->status);

        $this->post(route('admissions.tracks.store'), [
            'period_id' => $period->id,
            'code' => 'DOMISILI',
            'name' => 'Domisili',
            'quota' => 30,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $track = AdmissionTrack::firstOrFail();
        $this->post(route('admissions.requirements.store'), [
            'track_id' => $track->id,
            'code' => 'KK',
            'label' => 'Kartu Keluarga',
            'is_required' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admission_document_requirements', [
            'track_id' => $track->id,
            'code' => 'KK',
            'is_required' => true,
        ]);
    }

    public function test_period_cannot_be_opened_without_track_and_required_configuration(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admissions.periods.store'), [
                'name' => 'SPMB 2027/2028',
                'academic_year' => '2027/2028',
                'level' => 'SMP',
                'registration_start' => '2027-01-01',
                'registration_end' => '2027-03-01',
            ]);

        $period = AdmissionPeriod::firstOrFail();
        $this->actingAs($this->admin)
            ->post(route('admissions.periods.status', $period), ['status' => 'registration_open'])
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $period->fresh()->status);
    }

    public function test_applicant_requires_period_track_and_rejects_duplicate_nisn(): void
    {
        [$period, $track] = $this->createConfiguredTrack();

        $payload = [
            'period_id' => $period->id,
            'track_id' => $track->id,
            'name' => 'Calon SPMB',
            'nisn' => '1234567890',
            'gender' => 'L',
        ];

        $this->actingAs($this->admin)
            ->post(route('admissions.applicants.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('admissions.applicants.store'), $payload)
            ->assertSessionHasErrors('nisn');

        $this->assertDatabaseCount('admission_applicants', 1);
    }

    public function test_document_is_private_and_can_be_verified_by_authorized_actor(): void
    {
        Storage::fake('local');
        [$period, $track] = $this->createConfiguredTrack();
        $applicant = AdmissionApplicant::create([
            'school_id' => $this->admin->school_id,
            'period_id' => $period->id,
            'track_id' => $track->id,
            'registration_no' => 'SPMB-2027-PRIVATE',
            'name' => 'Pendaftar Berkas',
            'gender' => 'P',
            'created_by' => $this->admin->id,
        ]);
        $requirement = $track->requirements()->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admissions.documents.store', $applicant), [
                'requirement_id' => $requirement->id,
                'file' => UploadedFile::fake()->create('kk.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $document = $applicant->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);

        $this->actingAs($this->admin)
            ->put(route('admissions.documents.verify', $document), [
                'status' => 'valid',
                'notes' => 'Berkas sesuai.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('valid', $document->fresh()->status);
    }

    public function test_ranking_and_finalization_are_audited_and_re_registration_creates_one_student(): void
    {
        [$period, $track] = $this->createConfiguredTrack();
        $this->actingAs($this->admin)->post(route('admissions.criteria.store'), [
            'track_id' => $track->id,
            'code' => 'SCORE',
            'label' => 'Nilai seleksi',
            'weight' => 1,
            'max_score' => 100,
        ]);
        $criterion = $track->criteria()->firstOrFail();
        $applicant = AdmissionApplicant::create([
            'school_id' => $this->admin->school_id,
            'period_id' => $period->id,
            'track_id' => $track->id,
            'registration_no' => 'SPMB-2027-SELECTED',
            'name' => 'Pendaftar Terpilih',
            'nisn' => '0987654321',
            'gender' => 'L',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('admissions.scores.store', $applicant), [
            'criterion_id' => $criterion->id,
            'score' => 90,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('admissions.selection.rank', $period))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->put(route('admissions.selection.finalize', $applicant), [
                'decision' => 'accepted',
                'override_reason' => 'Hasil rapat panitia.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('admissions.re_registration.confirm', $applicant), [
                'nis' => '20270001',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('admissions.re_registration.confirm', $applicant), [
                'nis' => '20270001',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'school_id' => $this->admin->school_id,
            'nis' => '20270001',
            'nisn' => '0987654321',
        ]);
        $this->assertDatabaseHas('admission_re_registrations', [
            'applicant_id' => $applicant->id,
            'status' => 'confirmed',
        ]);
        $this->assertTrue(AuditEvent::where('event_type', 'admissions.selection.finalized')->exists());
        $this->assertTrue(AuditEvent::where('event_type', 'admissions.re_registration.confirmed')->exists());
    }

    /** @return array{0: AdmissionPeriod, 1: AdmissionTrack} */
    private function createConfiguredTrack(): array
    {
        $this->actingAs($this->admin)->post(route('admissions.periods.store'), [
            'name' => 'SPMB 2027/2028',
            'academic_year' => '2027/2028',
            'level' => 'SMP',
            'registration_start' => '2027-01-01',
            'registration_end' => '2027-03-01',
        ]);
        $period = AdmissionPeriod::firstOrFail();

        $this->actingAs($this->admin)->post(route('admissions.tracks.store'), [
            'period_id' => $period->id,
            'code' => 'DOMISILI',
            'name' => 'Domisili',
            'quota' => 30,
        ]);
        $track = AdmissionTrack::firstOrFail();

        $this->actingAs($this->admin)->post(route('admissions.requirements.store'), [
            'track_id' => $track->id,
            'code' => 'KK',
            'label' => 'Kartu Keluarga',
            'is_required' => true,
        ]);

        return [$period->fresh(), $track->fresh()];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Infrastructure\Models\PublicIkmSurvey;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IkmInstrumentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::query()->create(['name' => 'Madrasah Pelayanan', 'npsn' => '12340004', 'level' => 'MA', 'timezone' => 'Asia/Makassar']);
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $this->admin = User::query()->create(['school_id' => $this->school->id, 'role_id' => $role->id,
            'name' => 'Pengelola Layanan', 'email' => 'layanan@example.test', 'password' => 'test-password', 'is_active' => true]);
    }

    private function configure(array $overrides = [])
    {
        return $this->actingAs($this->admin)->put('/pengaturan/zona-integritas/konten', array_replace([
            'declaration' => 'Pelayanan terbuka dan akuntabel.', 'sop_category' => 'sop',
            'areas' => ['Mutu pelayanan'], 'service_options' => ['PTSP & Tata Usaha'],
            'questions' => [['id' => 'quality', 'label' => 'Mutu layanan', 'weight' => 2], ['id' => 'speed', 'label' => 'Kecepatan layanan', 'weight' => 1]],
            'answer_labels' => [1 => 'Sangat kurang', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Baik', 5 => 'Sangat baik'],
            'calculation_mode' => 'weighted_mean',
            'period' => ['id' => 'layanan-semester', 'label' => 'Survei layanan semester', 'starts_at' => null, 'ends_at' => null],
        ], $overrides));
    }

    private function responseData(array $overrides = []): array
    {
        return array_replace(['respondent_name' => 'Pengunjung Perpustakaan', 'respondent_role' => 'umum',
            'service_name' => 'PTSP & Tata Usaha', 'rating' => 1,
            'instrument_version' => app(PublicPortalService::class)->ikmInstrument($this->school->fresh())['instrument_version'],
            'submission_key' => (string) Str::uuid(), 'answers' => ['quality' => 5, 'speed' => 1],
            'comment' => 'Layanan jelas dan mudah diikuti.'], $overrides);
    }

    public function test_stale_admin_configuration_and_html_only_answer_labels_are_rejected(): void
    {
        $revision = app(PublicPortalService::class)->integrityZoneConfig($this->school)['config_revision'];
        $this->configure(['expected_revision' => $revision])->assertSessionHasNoErrors();
        $this->configure(['expected_revision' => $revision, 'declaration' => 'A stale editor overwrite.'])
            ->assertSessionHasErrors('expected_revision');
        $this->assertSame('Pelayanan terbuka dan akuntabel.', data_get($this->school->fresh()->settings, 'integrity_zone.declaration'));
        $this->configure(['answer_labels' => [1 => '<b></b>', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Baik', 5 => 'Sangat baik']])
            ->assertSessionHasErrors('answer_labels.1');
    }

    public function test_versionless_legacy_form_is_rejected_after_the_overall_question_changes(): void
    {
        $this->configure(['questions' => [['id' => 'overall', 'label' => 'Penilaian ketepatan waktu', 'weight' => 1]]])->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', ['respondent_name' => 'Pengunjung', 'respondent_role' => 'umum',
            'service_name' => 'PTSP', 'rating' => 5])->assertSessionHasErrors('instrument_version');
        $this->assertDatabaseCount('public_ikm_surveys', 0);
    }

    public function test_weighted_result_uses_server_configuration_and_precise_snapshot(): void
    {
        $this->configure()->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', $this->responseData(['weights' => ['quality' => 9999]]))->assertSessionHasNoErrors();
        $survey = PublicIkmSurvey::query()->firstOrFail();
        $this->assertSame(3.67, $survey->rating);
        $this->assertSame(3.67, $survey->instrument_snapshot['score']);
        $this->assertSame(4, (int) $survey->getRawOriginal('rating'));
        $this->assertSame(['quality' => 5, 'speed' => 1], $survey->instrument_snapshot['answers']);
        $survey->update(['is_approved' => true]);
        $this->assertSame(3.67, app(PublicPortalService::class)->ikmSummary($this->school->id)['average_rating']);
    }

    public function test_changing_weights_preserves_previous_instrument_and_rejects_stale_forms(): void
    {
        $this->configure()->assertSessionHasNoErrors();
        $old = $this->responseData();
        $this->post('/zona-integritas/ikm', $old)->assertSessionHasNoErrors();
        $survey = PublicIkmSurvey::query()->firstOrFail();
        $snapshot = $survey->instrument_snapshot;
        $this->configure(['questions' => [['id' => 'quality', 'label' => 'Kualitas yang diperbarui', 'weight' => 1], ['id' => 'speed', 'label' => 'Kecepatan baru', 'weight' => 9]]])
            ->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', array_replace($old, ['submission_key' => (string) Str::uuid()]))
            ->assertSessionHasErrors('instrument_version');
        $this->assertSame($snapshot, $survey->fresh()->instrument_snapshot);
        $this->assertDatabaseCount('public_ikm_surveys', 1);
    }

    public function test_submission_retry_is_idempotent_and_does_not_replace_answers(): void
    {
        $this->configure()->assertSessionHasNoErrors();
        $data = $this->responseData();
        $this->post('/zona-integritas/ikm', $data)->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', array_replace($data, ['answers' => ['quality' => 1, 'speed' => 5]]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('public_ikm_surveys', 1);
        $this->assertSame(3.67, PublicIkmSurvey::query()->firstOrFail()->rating);
    }

    public function test_period_closure_invalid_questions_and_zero_weights_do_not_save(): void
    {
        $this->configure(['questions' => [['id' => 'quality', 'label' => 'Mutu layanan', 'weight' => 0]]])->assertSessionHasErrors('questions.0.weight');
        $this->configure()->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', $this->responseData(['answers' => ['quality' => 5]]))->assertSessionHasErrors('answers.speed');
        $this->post('/zona-integritas/ikm', $this->responseData(['answers' => ['quality' => 5, 'speed' => 1, 'unknown' => 5]]))->assertSessionHasErrors('answers');
        $this->configure(['period' => ['id' => 'belum-buka', 'label' => 'Survei berikutnya', 'starts_at' => now()->addDay()->toIso8601String(), 'ends_at' => null]])
            ->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', $this->responseData())->assertSessionHasErrors('service');
        $this->assertDatabaseCount('public_ikm_surveys', 0);
    }

    public function test_legacy_scores_keep_their_meaning_when_averaged_with_new_snapshots(): void
    {
        $this->configure()->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', $this->responseData())->assertSessionHasNoErrors();
        PublicIkmSurvey::query()->firstOrFail()->update(['is_approved' => true]);
        PublicIkmSurvey::query()->create(['school_id' => $this->school->id, 'respondent_role' => 'umum',
            'service_name' => 'Layanan lama', 'rating' => 2, 'is_approved' => true]);
        $summary = app(PublicPortalService::class)->ikmSummary($this->school->id);
        $this->assertSame(2.84, $summary['average_rating']);
        $this->assertSame(2, $summary['total_responses']);
    }

    public function test_snapshot_is_immutable_after_submission(): void
    {
        $this->configure()->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', $this->responseData())->assertSessionHasNoErrors();
        $survey = PublicIkmSurvey::query()->firstOrFail();
        $this->expectException(\LogicException::class);
        $survey->update(['instrument_snapshot' => ['score' => 5]]);
    }

    public function test_csv_export_is_school_scoped_and_protects_formula_cells(): void
    {
        $this->configure()->assertSessionHasNoErrors();
        $this->post('/zona-integritas/ikm', $this->responseData(['respondent_name' => '=HYPERLINK("https://outside.invalid")', 'comment' => '  +SUM(1,2)']))
            ->assertSessionHasNoErrors();
        $other = School::query()->create(['name' => 'Sekolah lain', 'npsn' => '12340005', 'level' => 'MA']);
        PublicIkmSurvey::query()->create(['school_id' => $other->id, 'respondent_name' => 'PRIVATE_OTHER_SCHOOL',
            'respondent_role' => 'umum', 'service_name' => 'PTSP', 'rating' => 5]);
        $csv = $this->get('/pengaturan/zona-integritas/ekspor')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+SUM", $csv);
        $this->assertStringNotContainsString('PRIVATE_OTHER_SCHOOL', $csv);
        $this->assertStringContainsString('3.67', $csv);
        $this->app['auth']->forgetGuards();
        $this->get('/pengaturan/zona-integritas/ekspor')->assertRedirect('/login');
    }
}

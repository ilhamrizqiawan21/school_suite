<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Assessment\Application\Services\AssessmentWorkbookService;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\QuestionPack;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class QuestionWorkflowParityTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(string $username = 'guru'): User
    {
        $school = School::firstOrCreate(['npsn' => '12345678'], ['name' => 'Sekolah Uji', 'status' => 'active']);
        $role = Role::firstOrCreate(['name' => 'teacher'], ['label' => 'Guru', 'is_system' => true]);

        return User::factory()->create(['school_id' => $school->id, 'role_id' => $role->id, 'username' => $username, 'is_active' => true]);
    }

    public function test_analysis_draft_roundtrips_incomplete_values_and_is_private(): void
    {
        $owner = $this->teacher();
        $other = $this->teacher('guru2');
        $draft = ['nama' => '', 'kelas' => '', 'jumlahSoal' => 20, 'pilihan' => 4, 'kunci' => array_fill(0, 20, ''), 'siswa' => [['absen' => '1', 'jawaban' => '']], 'langkah' => 1];
        $this->actingAs($owner)->putJson('/penilaian/analisis/cepat/draft', $draft)->assertNoContent();
        $this->getJson('/penilaian/analisis/cepat/draft')->assertJson(['draft' => $draft]);
        $this->actingAs($other)->getJson('/penilaian/analisis/cepat/draft')->assertJsonPath('draft', null);
        $this->actingAs($owner)->deleteJson('/penilaian/analisis/cepat/draft')->assertNoContent();
        $this->getJson('/penilaian/analisis/cepat/draft')->assertJsonPath('draft', null);
    }

    public function test_workbook_export_reimports_three_sheets_and_keeps_formulas_inert(): void
    {
        $this->actingAs($this->teacher());
        $sheets = ['Jawaban' => [['Nama Tes', '=1+1'], ['Absen', '1', '2'], ['1', 'A', 'B']], 'Hasil' => [['No.', 'P']], 'Ringkasan' => [['Siswa', 5]]];
        $response = $this->postJson('/penilaian/analisis/cepat/export', ['sheets' => $sheets])->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'analysis-test');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            $this->assertSame(['Jawaban', 'Hasil', 'Ringkasan'], $book->getSheetNames());
            $this->assertSame('s', $book->getSheet(0)->getCell('B1')->getDataType());
            $book->disconnectWorksheets();
            $file = new UploadedFile($path, 'analisis.xlsx', null, null, true);
            $this->post('/penilaian/analisis/cepat/import', ['file' => $file], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('rows.0.1', '=1+1');
        } finally {
            @unlink($path);
        }
    }

    public function test_spreadsheet_formula_is_rejected(): void
    {
        $this->actingAs($this->teacher());
        $book = app(AssessmentWorkbookService::class)->workbook(['Jawaban' => [['Absen', '1']]]);
        $book->getActiveSheet()->setCellValue('A2', '=1+1');
        $path = tempnam(sys_get_temp_dir(), 'formula-test');
        try {
            IOFactory::createWriter($book, 'Xlsx')->save($path);
            $this->post('/penilaian/analisis/cepat/import', ['file' => new UploadedFile($path, 'formula.xlsx', null, null, true)], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function test_bank_metadata_package_snapshot_and_documents_keep_reference_order(): void
    {
        $owner = $this->teacher();
        $this->actingAs($owner);
        $question = ['type' => 'multiple_choice', 'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'prompt' => 'Dua tambah dua?', 'cognitive_level' => 'C3', 'source' => 'Buku guru', 'class_label' => 'VII A', 'options' => ['3', '4', '5'], 'correct_answer' => '4', 'points' => 1];
        $this->post('/penilaian/bank-soal', $question)->assertSessionHasNoErrors()->assertRedirect();
        $q = ExamQuestion::firstOrFail();
        $this->assertSame('C3', $q->currentVersionRecord()->cognitive_level);
        $this->assertSame('Buku guru', $q->currentVersionRecord()->source);
        $pack = ['name' => 'Sumatif', 'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'randomize_questions' => false, 'randomize_options' => false, 'status' => 'draft', 'items' => [['question_id' => $q->id, 'points' => 1]], 'document_settings' => ['mapel' => 'Matematika', 'kelas' => 'VII A', 'paper' => ['ukuran' => 'F4', 'orientasi' => 'landscape', 'columns' => 3], 'kop' => ['namaLembaga' => 'Sekolah Uji']]];
        $this->post('/penilaian/paket-soal', $pack)->assertSessionHasNoErrors()->assertRedirect();
        $p = QuestionPack::firstOrFail();
        $versionId = $p->items()->first()->question_version_id;
        $this->put('/penilaian/bank-soal/'.$q->id, [...$question, 'prompt' => 'Soal baru'])->assertSessionHasNoErrors();
        $this->put('/penilaian/paket-soal/'.$p->id, $pack)->assertSessionHasNoErrors();
        $this->assertSame($versionId, $p->items()->first()->question_version_id);
        $this->get('/penilaian/paket-soal/'.$p->id.'/dokumen')->assertInertia(fn (Assert $page) => $page->component('Assessment/QuestionPacks/Documents')->where('questions.0.teks', 'Dua tambah dua?')->where('questions.0.kunci', 'B')->where('pack.settings.paper.columns', 3));
        $this->post('/penilaian/paket-soal/'.$p->id.'/duplikasi')->assertRedirect();
        $this->assertSame($p->document_settings, QuestionPack::latest('id')->first()->document_settings);
        $this->actingAs($this->teacher('guru2'))->get('/penilaian/paket-soal/'.$p->id.'/dokumen')->assertForbidden();
    }

    public function test_reference_csv_import_skips_duplicate_rows(): void
    {
        $this->actingAs($this->teacher());
        $csv = "Teks Soal,Opsi A,Opsi B,Opsi C,Jawaban PG,Level Kognitif,Kelas,Sumber\nBerapa 2+2?,3,4,5,B,C3,VII A,Buku\nBerapa 2+2?,3,4,5,B,C3,VII A,Buku";
        $this->post('/penilaian/bank-soal/import/preview', ['file' => UploadedFile::fake()->createWithContent('bank.csv', $csv)])->assertInertia(fn (Assert $page) => $page->where('valid_count', 2));
        $preview = $this->post('/penilaian/bank-soal/import/preview', ['file' => UploadedFile::fake()->createWithContent('bank.csv', $csv)]);
        $token = $preview->viewData('page')['props']['token'];
        $this->post('/penilaian/bank-soal/import/'.$token.'/confirm', ['skip_duplicates' => true])->assertRedirect();
        $this->assertDatabaseCount('exam_questions', 1);
        $this->assertDatabaseHas('exam_questions', ['source' => 'Buku', 'class_label' => 'VII A']);
    }
}

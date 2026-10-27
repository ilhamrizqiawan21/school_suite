<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingDocument;
use App\Modules\Academic\Infrastructure\Models\TeachingJournal;
use App\Modules\Academic\Infrastructure\Models\TeachingPlan;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class TeachingPlanningDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private TeachingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $school = School::create(['npsn' => '87654321', 'name' => 'Sekolah Uji', 'status' => 'active']);
        $role = Role::create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);
        foreach (['owner', 'other'] as $key) {
            $user = User::create([
                'school_id' => $school->id, 'role_id' => $role->id, 'name' => $key,
                'username' => $key, 'email' => $key.'@school.test', 'password' => bcrypt('secret'), 'is_active' => true,
            ]);
            Teacher::create(['school_id' => $school->id, 'user_id' => $user->id, 'nip' => $key, 'name' => $key, 'gender' => 'L']);
            $this->{$key} = $user;
        }
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]);
        $term = $year->terms()->create(['name' => 'Semester Ganjil', 'semester_type' => 'odd', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $subject = Subject::create(['school_id' => $school->id, 'code' => 'MTK', 'name' => 'Matematika']);
        $teacherId = Teacher::query()->where('user_id', $this->owner->id)->value('id');
        $this->plan = TeachingPlan::create([
            'school_id' => $school->id, 'teacher_id' => $teacherId, 'term_id' => $term->id,
            'subject_id' => $subject->id, 'grade_level' => 7, 'curriculum' => 'merdeka',
            'data' => ['chapters' => [['id' => 'b1', 'title' => 'Bilangan', 'semester' => '1']], 'units' => [['id' => 'u1', 'chapter_id' => 'b1', 'jp' => 5]]],
        ]);
    }

    public function test_document_crud_is_scoped_to_owner_active_term_and_plan(): void
    {
        $payload = ['plan_id' => $this->plan->id, 'academic_term_id' => $this->plan->term_id, 'type' => 'promes', 'title' => 'Promes Ganjil', 'semester' => 'ganjil', 'content' => ['chapters' => [], 'units' => []]];
        $this->actingAs($this->owner)->post('/akademik/perangkat/dokumen', $payload)->assertRedirect();
        $document = TeachingDocument::query()->sole();
        $this->assertSame($this->plan->id, $document->plan_id);

        $this->actingAs($this->owner)->put('/akademik/perangkat/dokumen/'.$document->id, [...$payload, 'title' => 'Promes Diperbarui'])->assertRedirect();
        $this->assertSame('Promes Diperbarui', $document->fresh()->title);

        $this->actingAs($this->other)->put('/akademik/perangkat/dokumen/'.$document->id, $payload)->assertNotFound();
        $this->actingAs($this->owner)->delete('/akademik/perangkat/dokumen/'.$document->id)->assertRedirect();
        $this->assertDatabaseMissing('teaching_documents', ['id' => $document->id]);
    }

    public function test_package_page_only_contains_the_signed_in_teachers_plans(): void
    {
        $this->actingAs($this->owner)->get('/akademik/perangkat/paket-administrasi')
            ->assertOk()->assertInertia(fn ($page) => $page->component('Academic/Planning/Package')->has('packages', 1));
    }

    public function test_package_download_is_a_zip_with_manifest_and_generated_docx(): void
    {
        $response = $this->actingAs($this->owner)->get('/akademik/perangkat/paket-administrasi/unduh');
        $response->assertOk()->assertHeader('content-type', 'application/zip');
        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        self::assertTrue($zip->open($zipPath) === true);
        self::assertNotFalse($zip->locateName('MANIFEST.txt'));
        $atpDocumentExists = false;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            if (str_contains((string) $zip->getNameIndex($index), 'matematika-kelas-7-atp-silabus.docx')) {
                $atpDocumentExists = true;
            }
        }
        self::assertTrue($atpDocumentExists);
        $manifest = $zip->getFromName('MANIFEST.txt');
        self::assertStringContainsString('Dilewati', (string) $manifest);
        $zip->close();
    }

    public function test_package_contains_saved_journal_sessions_for_matching_plan_only(): void
    {
        $class = ClassGroup::create([
            'school_id' => $this->plan->school_id, 'academic_year_id' => $this->plan->term->academic_year_id,
            'grade_level' => 7, 'name' => 'VII-A', 'capacity' => 30,
        ]);
        $session = LessonSession::create([
            'school_id' => $this->plan->school_id, 'term_id' => $this->plan->term_id,
            'class_group_id' => $class->id, 'subject_id' => $this->plan->subject_id,
            'teacher_id' => $this->plan->teacher_id, 'date' => '2026-08-10',
            'start_time' => '07:00', 'end_time' => '08:30', 'meeting_number' => 1, 'status' => 'completed',
        ]);
        TeachingJournal::create(['lesson_session_id' => $session->id, 'topic' => 'Bilangan bulat', 'learning_objectives' => 'Mengurutkan bilangan']);

        $response = $this->actingAs($this->owner)->get('/akademik/perangkat/paket-administrasi/unduh');
        $zip = new ZipArchive;
        self::assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $manifest = (string) $zip->getFromName('MANIFEST.txt');
        self::assertStringContainsString('Jurnal pertemuan semester Ganjil: Disiapkan', $manifest);
        $meetingDocumentName = null;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (str_contains((string) $name, 'jurnal-pertemuan-semester-ganjil.docx')) {
                $meetingDocumentName = $name;
            }
        }
        self::assertNotNull($meetingDocumentName);
        self::assertStringStartsWith('PK', (string) $zip->getFromName($meetingDocumentName));
        $zip->close();
    }
}

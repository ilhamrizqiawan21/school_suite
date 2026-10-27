<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DemoAcademicContentSeeder;
use Database\Seeders\DemoSchoolOperationsSeeder;
use Database\Seeders\PilotSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSchoolOperationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_operations_are_additive_repeatable_and_preserve_edits(): void
    {
        $this->seed(PilotSchoolSeeder::class);
        $this->seed(DemoAcademicContentSeeder::class);
        $existingScores = DB::table('scores')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $existingStudents = DB::table('students')->orderBy('id')->pluck('status', 'id')->all();
        $this->seed(DemoSchoolOperationsSeeder::class);
        $tables = ['teacher_leave_requests', 'teacher_attendance_records', 'facility_assets', 'facility_loans', 'correspondence_records',
            'administration_staff_tasks', 'admission_applicants', 'admission_committee_members', 'student_notes', 'student_incidents',
            'student_achievements', 'assistance_records', 'attendance_dispensations', 'administration_student_ledger_details', 'teacher_substitute_assignments', 'student_lifecycle_events', 'remedial_records', 'user_notifications'];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
            $this->assertGreaterThan(0, $counts[$table], $table);
        }
        foreach ($existingScores as $score) {
            $this->assertDatabaseHas('scores', $score);
        }
        foreach ($existingStudents as $studentId => $status) {
            $this->assertDatabaseHas('students', ['id' => $studentId, 'status' => $status]);
        }
        $this->assertTrue(DB::table('teacher_substitute_assignments')
            ->where('notes', '[DEMO] Riwayat pengganti guru yang dapat ditinjau.')
            ->whereDate('date', '<=', today())->exists());
        $this->assertDatabaseHas('teacher_leave_requests', ['status' => 'approved']);
        $this->assertDatabaseHas('teacher_attendance_records', ['status' => 'sick']);
        $attendance = DB::table('teacher_attendance_records')->where('status', 'present')->first();
        DB::table('teacher_attendance_records')->where('id', $attendance->id)->update(['status' => 'permitted', 'notes' => 'Catatan operator']);
        DB::table('facility_assets')->where('inventory_code', 'DEMO-SAR-2')->update(['name' => 'Nama hasil penyuntingan']);
        $this->seed(DemoSchoolOperationsSeeder::class);
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertDatabaseHas('teacher_attendance_records', ['id' => $attendance->id, 'status' => 'permitted', 'notes' => 'Catatan operator']);
        $this->assertDatabaseHas('facility_assets', ['inventory_code' => 'DEMO-SAR-2', 'name' => 'Nama hasil penyuntingan']);
    }

    public function test_it_skips_databases_without_the_pilot_school(): void
    {
        $this->seed(DemoSchoolOperationsSeeder::class);
        $this->assertDatabaseCount('teacher_leave_requests', 0);
        $this->assertDatabaseCount('facility_assets', 0);
        $this->assertDatabaseCount('admission_applicants', 0);
    }
}

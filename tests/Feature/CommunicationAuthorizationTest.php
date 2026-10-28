<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Administration\Infrastructure\Models\AcademicCalendarEvent;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunicationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users = [];

    private School $school;

    private ClassGroup $classGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Communication Authorization Test',
            'npsn' => '12345678',
            'level' => 'SMA',
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $this->classGroup = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 10,
            'name' => 'X-TEST',
        ]);

        foreach (['admin', 'operator', 'teacher', 'homeroom', 'parent'] as $roleName) {
            $role = Role::create([
                'name' => $roleName,
                'label' => $roleName,
                'is_system' => true,
            ]);

            $this->users[$roleName] = User::create([
                'school_id' => $this->school->id,
                'role_id' => $role->id,
                'name' => ucfirst($roleName).' User',
                'username' => $roleName.'.communication',
                'email' => $roleName.'@communication.test',
                'password' => 'secret123',
                'is_active' => true,
            ]);
        }
    }

    public function test_parent_is_denied_from_communication_and_attachment_downloads(): void
    {
        Storage::fake('local');
        $announcement = Announcement::create([
            'school_id' => $this->school->id,
            'author_id' => $this->users['admin']->id,
            'class_group_id' => $this->classGroup->id,
            'audience_role' => 'parent',
            'title' => 'Parent-only announcement',
            'body' => 'Private communication.',
            'attachment_path' => 'communication/announcements/private.pdf',
            'attachment_name' => 'private.pdf',
            'published_at' => now(),
        ]);
        Storage::disk('local')->put($announcement->attachment_path, 'PRIVATE');

        $this->actingAs($this->users['parent'])->get('/komunikasi')->assertForbidden();
        $this->get('/komunikasi/pengumuman/'.$announcement->id.'/lampiran')->assertForbidden();
    }

    public function test_teacher_and_homeroom_can_delete_only_their_own_communication_records(): void
    {
        $announcement = Announcement::create([
            'school_id' => $this->school->id,
            'author_id' => $this->users['admin']->id,
            'title' => 'Admin announcement',
            'body' => 'Owned by admin.',
            'published_at' => now(),
        ]);
        $event = AcademicCalendarEvent::create([
            'school_id' => $this->school->id,
            'created_by' => $this->users['admin']->id,
            'title' => 'Admin event',
            'event_type' => 'academic',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($this->users['teacher'])->delete('/komunikasi/pengumuman/'.$announcement->id)->assertForbidden();
        $this->actingAs($this->users['homeroom'])->delete('/komunikasi/kalender/'.$event->id)->assertForbidden();
        $this->assertDatabaseHas('announcements', ['id' => $announcement->id]);
        $this->assertDatabaseHas('academic_calendar_events', ['id' => $event->id]);

        $this->actingAs($this->users['teacher'])->post('/komunikasi/pengumuman', [
            'title' => 'Teacher announcement',
            'body' => 'Created by teacher.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $teacherAnnouncement = Announcement::query()->where('title', 'Teacher announcement')->firstOrFail();
        $this->delete('/komunikasi/pengumuman/'.$teacherAnnouncement->id)->assertRedirect();
        $this->assertDatabaseMissing('announcements', ['id' => $teacherAnnouncement->id]);

        $this->actingAs($this->users['homeroom'])->post('/komunikasi/kalender', [
            'title' => 'Homeroom event',
            'event_type' => 'meeting',
            'starts_at' => now()->addDay()->toDateTimeString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $homeroomEvent = AcademicCalendarEvent::query()->where('title', 'Homeroom event')->firstOrFail();
        $this->delete('/komunikasi/kalender/'.$homeroomEvent->id)->assertRedirect();
        $this->assertDatabaseMissing('academic_calendar_events', ['id' => $homeroomEvent->id]);
    }

    public function test_admin_and_operator_can_delete_any_same_school_record(): void
    {
        $announcement = Announcement::create([
            'school_id' => $this->school->id,
            'author_id' => $this->users['teacher']->id,
            'title' => 'Teacher announcement for admin',
            'body' => 'Created by teacher.',
            'published_at' => now(),
        ]);
        $event = AcademicCalendarEvent::create([
            'school_id' => $this->school->id,
            'created_by' => $this->users['homeroom']->id,
            'title' => 'Homeroom event for operator',
            'event_type' => 'meeting',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($this->users['admin'])->delete('/komunikasi/pengumuman/'.$announcement->id)->assertRedirect();
        $this->actingAs($this->users['operator'])->delete('/komunikasi/kalender/'.$event->id)->assertRedirect();
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
        $this->assertDatabaseMissing('academic_calendar_events', ['id' => $event->id]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PilotSchoolSeeder::class,
            DemoAcademicContentSeeder::class,
            DemoRosterSeeder::class,
            DemoTeacherSessionsSeeder::class,
            DemoTeachingPlanningSeeder::class,
            DemoAssessmentContentSeeder::class,
            DemoLearningCommunicationSeeder::class,
            DemoCommunicationSeeder::class,
            DemoCorrespondenceSeeder::class,
            DemoStaffFacilitiesSeeder::class,
            DemoReportSeeder::class,
        ]);
    }
}

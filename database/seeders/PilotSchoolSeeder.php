<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Assessment\Application\Services\ReportPublishingService;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PilotSchoolSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions Baku
        $roles = [
            'admin' => Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator', 'is_system' => true]),
            'principal' => Role::firstOrCreate(['name' => 'principal'], ['label' => 'Kepala Sekolah', 'is_system' => true]),
            'operator' => Role::firstOrCreate(['name' => 'operator'], ['label' => 'Operator Sekolah', 'is_system' => true]),
            'teacher' => Role::firstOrCreate(['name' => 'teacher'], ['label' => 'Guru Mata Pelajaran', 'is_system' => true]),
            'homeroom' => Role::firstOrCreate(['name' => 'homeroom'], ['label' => 'Wali Kelas', 'is_system' => true]),
            'counselor' => Role::firstOrCreate(['name' => 'counselor'], ['label' => 'Guru BK', 'is_system' => true]),
            'student' => Role::firstOrCreate(['name' => 'student'], ['label' => 'Siswa', 'is_system' => true]),
        ];

        // Buat Permissions Core, Academic, Homeroom, Assessment
        foreach (PermissionEnum::cases() as $permCase) {
            Permission::firstOrCreate(
                ['name' => $permCase->value],
                [
                    'label' => ucwords(str_replace(['.', '_'], ' ', $permCase->value)),
                    'module' => explode('.', $permCase->value)[0] ?? 'core',
                ]
            );
        }

        $corePermissions = Permission::query()
            ->whereIn('name', [
                PermissionEnum::USERS_VIEW->value,
                PermissionEnum::USERS_CREATE->value,
                PermissionEnum::SCHOOL_VIEW->value,
                PermissionEnum::SCHOOL_UPDATE->value,
            ])
            ->pluck('id', 'name');
        $roles['operator']->permissions()->syncWithoutDetaching([
            $corePermissions[PermissionEnum::USERS_VIEW->value],
            $corePermissions[PermissionEnum::USERS_CREATE->value],
            $corePermissions[PermissionEnum::SCHOOL_VIEW->value],
            $corePermissions[PermissionEnum::SCHOOL_UPDATE->value],
        ]);
        $roles['principal']->permissions()->syncWithoutDetaching([
            $corePermissions[PermissionEnum::USERS_VIEW->value],
            $corePermissions[PermissionEnum::SCHOOL_VIEW->value],
        ]);

        // 2. Profil Sekolah Pilot
        $school = School::firstOrCreate(
            ['npsn' => '20109988'],
            [
                'name' => 'SMP Negeri 1 Percontohan Nusantara',
                'level' => 'SMP',
                'address' => 'Jl. Pendidikan Merdeka No. 45, Jakarta Pusat',
                'phone' => '021-5550199',
                'email' => 'info@smpn1percontohan.sch.id',
            ]
        );

        $defaultPassword = Hash::make('password');

        // 3. Akun Pengguna Utama
        $admin = User::firstOrCreate(
            ['email' => 'admin@pilot.sch.id'],
            [
                'school_id' => $school->id,
                'role_id' => $roles['admin']->id,
                'username' => 'admin_pilot',
                'name' => 'Administrator Pilot',
                'password' => $defaultPassword,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $this->requirePilotPasswordRotation($admin);

        $principal = User::firstOrCreate(
            ['email' => 'kepsek@pilot.sch.id'],
            [
                'school_id' => $school->id,
                'role_id' => $roles['principal']->id,
                'username' => 'kepsek_pilot',
                'name' => 'Drs. H. Mulyadi, M.Pd.',
                'password' => $defaultPassword,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $this->requirePilotPasswordRotation($principal);

        $operator = User::firstOrCreate(
            ['email' => 'operator@pilot.sch.id'],
            [
                'school_id' => $school->id,
                'role_id' => $roles['operator']->id,
                'username' => 'operator_pilot',
                'name' => 'Rian Hidayat, S.Kom.',
                'password' => $defaultPassword,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $this->requirePilotPasswordRotation($operator);

        // 4. Tahun Ajaran & Semester
        $ayPast = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2025/2026'],
            ['start_date' => '2025-07-15', 'end_date' => '2026-06-25', 'is_active' => false]
        );

        $ayActive = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026/2027'],
            ['start_date' => '2026-07-15', 'end_date' => '2027-06-25', 'is_active' => true]
        );

        $termActive = Term::firstOrCreate(
            ['academic_year_id' => $ayActive->id, 'name' => 'Semester Ganjil'],
            ['semester_type' => 'odd', 'start_date' => '2026-07-15', 'end_date' => '2026-12-20', 'is_active' => true]
        );

        $termEven = Term::firstOrCreate(
            ['academic_year_id' => $ayActive->id, 'name' => 'Semester Genap'],
            ['semester_type' => 'even', 'start_date' => '2027-01-05', 'end_date' => '2027-06-20', 'is_active' => false]
        );

        // 5. Mata Pelajaran Baku
        $subjectsData = [
            ['code' => 'BIN-01', 'name' => 'Bahasa Indonesia'],
            ['code' => 'MAT-01', 'name' => 'Matematika'],
            ['code' => 'BIG-01', 'name' => 'Bahasa Inggris'],
            ['code' => 'IPA-01', 'name' => 'Ilmu Pengetahuan Alam (IPA)'],
            ['code' => 'IPS-01', 'name' => 'Ilmu Pengetahuan Sosial (IPS)'],
            ['code' => 'PKN-01', 'name' => 'Pendidikan Pancasila (PPKn)'],
            ['code' => 'INF-01', 'name' => 'Informatika'],
            ['code' => 'PJK-01', 'name' => 'Pendidikan Jasmani & Olahraga'],
            ['code' => 'PAI-01', 'name' => 'Pendidikan Agama & Budi Pekerti'],
            ['code' => 'SNB-01', 'name' => 'Seni Budaya'],
        ];

        $subjects = [];
        foreach ($subjectsData as $sData) {
            $subjects[$sData['code']] = Subject::firstOrCreate(
                ['school_id' => $school->id, 'code' => $sData['code']],
                ['name' => $sData['name'], 'is_active' => true]
            );
        }

        // 6. Guru-guru Sekolah Pilot
        $teachersData = [
            ['name' => 'Budi Hartono, S.Pd.', 'nip' => '198001012005011001', 'email' => 'budi@pilot.sch.id', 'role' => 'homeroom', 'subject' => 'MAT-01'],
            ['name' => 'Siti Aminah, S.Pd.', 'nip' => '198203152006042002', 'email' => 'siti@pilot.sch.id', 'role' => 'homeroom', 'subject' => 'BIN-01'],
            ['name' => 'Agus Setiawan, M.Pd.', 'nip' => '198507202009021003', 'email' => 'agus@pilot.sch.id', 'role' => 'homeroom', 'subject' => 'BIG-01'],
            ['name' => 'Dewi Lestari, S.Si.', 'nip' => '198811102011012004', 'email' => 'dewi@pilot.sch.id', 'role' => 'teacher', 'subject' => 'IPA-01'],
            ['name' => 'Eko Prasetyo, S.Pd.', 'nip' => '199002142014021005', 'email' => 'eko@pilot.sch.id', 'role' => 'teacher', 'subject' => 'IPS-01'],
            ['name' => 'Rina Wijaya, S.Pd.', 'nip' => '199205182015032006', 'email' => 'rina@pilot.sch.id', 'role' => 'counselor', 'subject' => 'PKN-01'],
            ['name' => 'Fajar Nugraha, S.Kom.', 'nip' => '199308252019031007', 'email' => 'fajar@pilot.sch.id', 'role' => 'teacher', 'subject' => 'INF-01'],
        ];

        $teachers = [];
        foreach ($teachersData as $tData) {
            $tUser = User::firstOrCreate(
                ['email' => $tData['email']],
                [
                    'school_id' => $school->id,
                    'role_id' => $roles[$tData['role']]->id,
                    'username' => explode('@', $tData['email'])[0],
                    'name' => $tData['name'],
                    'password' => $defaultPassword,
                    'must_change_password' => true,
                    'is_active' => true,
                ]
            );
            $this->requirePilotPasswordRotation($tUser);

            $teachers[$tData['nip']] = Teacher::firstOrCreate(
                ['school_id' => $school->id, 'nip' => $tData['nip']],
                [
                    'user_id' => $tUser->id,
                    'name' => $tData['name'],
                    'gender' => str_contains($tData['name'], 'Siti') || str_contains($tData['name'], 'Dewi') || str_contains($tData['name'], 'Rina') ? 'P' : 'L',
                    'employment_status' => 'PNS',
                    'is_active' => true,
                ]
            );
        }

        // 7. Rombongan Belajar (Kelas 7A, 7B, 8A, 8B, 9A, 9B)
        $classesData = [
            ['name' => 'VII-A', 'grade_level' => 7, 'homeroom_nip' => '198001012005011001'],
            ['name' => 'VII-B', 'grade_level' => 7, 'homeroom_nip' => '198203152006042002'],
            ['name' => 'VIII-A', 'grade_level' => 8, 'homeroom_nip' => '198507202009021003'],
            ['name' => 'VIII-B', 'grade_level' => 8, 'homeroom_nip' => '199002142014021005'],
            ['name' => 'IX-A', 'grade_level' => 9, 'homeroom_nip' => '198811102011012004'],
            ['name' => 'IX-B', 'grade_level' => 9, 'homeroom_nip' => '199308252019031007'],
        ];

        $classGroups = [];
        foreach ($classesData as $cData) {
            $homeroomTeacher = $teachers[$cData['homeroom_nip']] ?? null;
            $classGroups[$cData['name']] = ClassGroup::firstOrCreate(
                ['school_id' => $school->id, 'academic_year_id' => $ayActive->id, 'name' => $cData['name']],
                [
                    'grade_level' => $cData['grade_level'],
                    'homeroom_teacher_id' => $homeroomTeacher?->id,
                    'capacity' => 32,
                ]
            );
        }

        // 8. Penugasan Mengajar
        foreach ($classGroups as $cg) {
            foreach ($teachersData as $tData) {
                $sub = $subjects[$tData['subject']] ?? null;
                $tch = $teachers[$tData['nip']] ?? null;
                if ($sub && $tch) {
                    TeachingAssignment::firstOrCreate([
                        'term_id' => $termActive->id,
                        'teacher_id' => $tch->id,
                        'subject_id' => $sub->id,
                        'class_group_id' => $cg->id,
                    ]);
                }
            }
        }

        // 9. Skema & Komponen Penilaian Kurikulum Merdeka
        $scheme = AssessmentScheme::firstOrCreate(
            ['school_id' => $school->id, 'code' => 'KM-SMP-2026', 'version' => 1],
            [
                'name' => 'Kurikulum Merdeka SMP 2026',
                'is_active' => true,
                'rounding_rule' => RoundingRuleEnum::HALF_UP,
                'decimal_places' => 2,
                'passing_grade_default' => 75.0,
            ]
        );

        $components = [
            ['name' => 'Formatif Harian 1', 'code' => 'F1', 'weight_percentage' => 30.0, 'passing_grade' => 75.0, 'sequence' => 1],
            ['name' => 'Tugas & Portofolio', 'code' => 'TUGAS', 'weight_percentage' => 20.0, 'passing_grade' => 75.0, 'sequence' => 2],
            ['name' => 'Sumatif Tengah Semester', 'code' => 'STS', 'weight_percentage' => 25.0, 'passing_grade' => 75.0, 'sequence' => 3],
            ['name' => 'Sumatif Akhir Semester', 'code' => 'SAS', 'weight_percentage' => 25.0, 'passing_grade' => 75.0, 'sequence' => 4],
        ];

        $componentModels = [];
        foreach ($components as $c) {
            $componentModels[$c['code']] = AssessmentComponent::firstOrCreate(
                ['scheme_id' => $scheme->id, 'code' => $c['code']],
                $c
            );
        }

        // 10. Data Siswa Sintetis Realistis (30 Siswa untuk Kelas VII-A)
        $studentsNames = [
            'Ahmad Fauzi', 'Aisyah Putri', 'Bagus Ramadhan', 'Citra Dewi', 'Dimas Prasetyo',
            'Eka Wulandari', 'Fajar Ramadhani', 'Gita Permata', 'Hadi Wijaya', 'Indah Puspitasari',
            'Joko Susilo', 'Kartika Sari', 'Lukman Hakim', 'Maya Anggraini', 'Naufal Pratama',
            'Nurul Hidayah', 'Oki Setiawan', 'Putri Ayu', 'Rahmat Hidayat', 'Rina Anggraini',
            'Rizky Kurniawan', 'Salma Zahra', 'Taufik Ismail', 'Tri Wahyuni', 'Umar Faruq',
            'Vina Melati', 'Wahyu Saputra', 'Yolanda Septiani', 'Zacky Ramadhan', 'Zulfa Amalia',
        ];

        $class7A = $classGroups['VII-A'];
        $enrolledStudents = [];

        foreach ($studentsNames as $idx => $name) {
            $nis = (string) (26001 + $idx);
            $nisn = '00'.(26000000 + $idx);
            $gender = in_array($name, ['Aisyah Putri', 'Citra Dewi', 'Eka Wulandari', 'Gita Permata', 'Indah Puspitasari', 'Kartika Sari', 'Maya Anggraini', 'Nurul Hidayah', 'Putri Ayu', 'Rina Anggraini', 'Salma Zahra', 'Tri Wahyuni', 'Vina Melati', 'Yolanda Septiani', 'Zulfa Amalia'], true) ? 'P' : 'L';

            $studentUser = User::firstOrCreate(
                ['email' => "siswa{$nis}@pilot.sch.id"],
                [
                    'school_id' => $school->id,
                    'role_id' => $roles['student']->id,
                    'username' => "siswa_{$nis}",
                    'name' => $name,
                    'password' => $defaultPassword,
                    'must_change_password' => true,
                    'is_active' => true,
                ]
            );
            $this->requirePilotPasswordRotation($studentUser);

            $student = Student::firstOrCreate(
                ['school_id' => $school->id, 'nis' => $nis],
                [
                    'user_id' => $studentUser->id,
                    'nisn' => $nisn,
                    'name' => $name,
                    'gender' => $gender,
                    'status' => 'active',
                ]
            );

            $enrollment = Enrollment::firstOrCreate(
                ['term_id' => $termActive->id, 'student_id' => $student->id],
                [
                    'class_group_id' => $class7A->id,
                    'status' => 'active',
                ]
            );

            $enrolledStudents[] = ['student' => $student, 'enrollment' => $enrollment];

            // Masukkan sampel nilai untuk siswa pertama (Ahmad Fauzi)
            if ($idx === 0) {
                Score::firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'assessment_component_id' => $componentModels['F1']->id],
                    [
                        'school_id' => $school->id,
                        'raw_score' => 88.0,
                        'final_score' => 88.0,
                        'notes' => 'Aktif dalam tanya jawab di kelas',
                        'recorded_by_user_id' => $admin->id,
                    ]
                );

                Score::firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'assessment_component_id' => $componentModels['TUGAS']->id],
                    [
                        'school_id' => $school->id,
                        'raw_score' => 92.0,
                        'final_score' => 92.0,
                        'notes' => 'Portofolio lengkap dan rapi',
                        'recorded_by_user_id' => $admin->id,
                    ]
                );

                // Buat 1 contoh ReportSnapshot berstatus PUBLISHED untuk verifikasi cetak instan
                $schemeSnapshot = ['name' => $scheme->name, 'code' => $scheme->code];
                $gradesSnapshot = [
                    [
                        'subject_id' => $subjects['MAT-01']->id,
                        'subject_name' => 'Matematika',
                        'subject_code' => 'MAT-01',
                        'teacher_name' => 'Budi Hartono, S.Pd.',
                        'final_score' => 89.5,
                        'letter_grade' => 'B',
                        'is_passing' => true,
                    ],
                    [
                        'subject_id' => $subjects['BIN-01']->id,
                        'subject_name' => 'Bahasa Indonesia',
                        'subject_code' => 'BIN-01',
                        'teacher_name' => 'Siti Aminah, S.Pd.',
                        'final_score' => 91.0,
                        'letter_grade' => 'A',
                        'is_passing' => true,
                    ],
                ];
                $attendanceSnapshot = ['present' => 95, 'sick' => 2, 'permitted' => 1, 'absent' => 0];
                $notesSnapshot = [
                    'homeroom_notes' => 'Menunjukkan prestasi belajar yang sangat baik dan konsisten. Terus pertahankan prestasimu!',
                ];

                ReportSnapshot::firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'term_id' => $termActive->id],
                    [
                        'school_id' => $school->id,
                        'class_group_id' => $class7A->id,
                        'student_id' => $student->id,
                        'scheme_snapshot' => $schemeSnapshot,
                        'grades_snapshot' => $gradesSnapshot,
                        'attendance_snapshot' => $attendanceSnapshot,
                        'notes_snapshot' => $notesSnapshot,
                        'status' => ReportStatusEnum::PUBLISHED,
                        'checksum' => app(ReportPublishingService::class)->calculateChecksum($schemeSnapshot, $gradesSnapshot, $attendanceSnapshot, $notesSnapshot),
                        'published_at' => now(),
                    ]
                );
            }
        }
    }

    private function requirePilotPasswordRotation(User $user): void
    {
        if (! $user->must_change_password && Hash::check('password', $user->password)) {
            $user->forceFill(['must_change_password' => true])->save();
        }
    }
}

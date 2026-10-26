<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Database\Seeder;

class DemoRosterSeeder extends Seeder
{
    private const STUDENT_NAMES = [
        'Ahmad Fauzi', 'Aisyah Putri', 'Bagus Ramadhan', 'Citra Dewi', 'Dimas Prasetyo',
        'Eka Wulandari', 'Fajar Ramadhani', 'Gita Permata', 'Hadi Wijaya', 'Indah Puspitasari',
        'Joko Susilo', 'Kartika Sari', 'Lukman Hakim', 'Maya Anggraini', 'Naufal Pratama',
        'Nurul Hidayah', 'Oki Setiawan', 'Putri Ayu', 'Rahmat Hidayat', 'Rina Anggraini',
        'Rizky Kurniawan', 'Salma Zahra', 'Taufik Ismail', 'Tri Wahyuni', 'Umar Faruq',
        'Vina Melati', 'Wahyu Saputra', 'Yolanda Septiani', 'Zacky Ramadhan', 'Zulfa Amalia',
    ];

    public function run(): void
    {
        $user = User::query()->where('email', 'dewi@pilot.sch.id')->first();
        if (! $user) {
            $this->command?->warn('Akun demo tidak ditemukan; roster tambahan dilewati.');

            return;
        }

        $term = Term::query()->whereHas('academicYear', fn ($query) => $query->where('school_id', $user->school_id))
            ->where('is_active', true)->first();
        if (! $term) {
            $this->command?->warn('Periode akademik aktif tidak ditemukan; roster tambahan dilewati.');

            return;
        }

        $classes = ClassGroup::query()->where('school_id', $user->school_id)
            ->where('academic_year_id', $term->academic_year_id)->orderBy('grade_level')->orderBy('name')->get();

        foreach ($classes as $classIndex => $class) {
            $enrollments = Enrollment::query()->where('term_id', $term->id)->where('class_group_id', $class->id)->get();
            $nextRoll = ((int) $enrollments->max('roll_number')) + 1;

            while ($enrollments->count() < 30) {
                $roll = $nextRoll++;
                $sequence = $classIndex * 100 + $roll;
                $nis = (string) (27000 + $sequence);
                $nameIndex = ($roll - 1 + $classIndex * 5) % count(self::STUDENT_NAMES);
                $student = Student::query()->firstOrCreate(
                    ['school_id' => $user->school_id, 'nis' => $nis],
                    [
                        'nisn' => '0027'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                        'name' => self::STUDENT_NAMES[$nameIndex],
                        'gender' => $roll % 2 === 0 ? 'P' : 'L',
                        'birth_place' => 'Nusantara',
                        'birth_date' => now()->subYears($class->grade_level + 5)->subDays($roll * 11)->toDateString(),
                        'religion' => 'Islam',
                        'address' => 'Jl. Pendidikan No. '.($roll + $classIndex * 30).', Nusantara',
                        'parent_name' => 'Orang Tua '.$nis,
                        'parent_phone' => '0812'.str_pad((string) (10000000 + $sequence), 8, '0', STR_PAD_LEFT),
                        'status' => 'active',
                    ]
                );

                $enrollment = Enrollment::query()->firstOrCreate(
                    ['term_id' => $term->id, 'student_id' => $student->id],
                    ['class_group_id' => $class->id, 'roll_number' => $roll, 'status' => 'active']
                );
                $enrollments->push($enrollment);
            }

            foreach ($enrollments as $enrollment) {
                if (AttendanceRecord::query()->where('school_id', $user->school_id)
                    ->where('enrollment_id', $enrollment->id)->whereDate('date', now()->toDateString())->exists()) {
                    continue;
                }

                $roll = (int) $enrollment->roll_number;
                [$status, $notes] = match ($roll % 10) {
                    4 => ['sick', 'Izin sakit dari orang tua.'],
                    7 => ['permitted', 'Izin mengikuti kegiatan keluarga.'],
                    9 => ['absent', 'Belum ada keterangan kehadiran.'],
                    default => ['present', null],
                };

                AttendanceRecord::query()->firstOrCreate(
                    ['school_id' => $user->school_id, 'enrollment_id' => $enrollment->id, 'date' => now()->toDateString(), 'type' => 'daily'],
                    ['lesson_session_id' => null, 'status' => $status, 'notes' => $notes, 'recorded_by_user_id' => null]
                );
            }
        }
    }
}

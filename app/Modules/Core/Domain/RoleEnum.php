<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case OPERATOR = 'operator';
    case PRINCIPAL = 'principal';
    case TEACHER = 'teacher';
    case HOMEROOM = 'homeroom';
    case COUNSELOR = 'counselor';
    case LIBRARIAN = 'librarian';
    case STUDENT = 'student';
    case PARENT = 'parent';
    case ALUMNI = 'alumni';
    case COMMITTEE = 'committee';
    case PTSP = 'ptsp';
    case RECEPTIONIST = 'receptionist';
    case PICKET = 'picket';
    case SECURITY_GUARD = 'security_guard';
    case TASK_FORCE = 'task_force';
    case SIGNATORY = 'signatory';

    /**
     * Get user-friendly label for each role.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::OPERATOR => 'Operator Tata Usaha',
            self::PRINCIPAL => 'Kepala Sekolah',
            self::TEACHER => 'Guru Mata Pelajaran',
            self::HOMEROOM => 'Wali Kelas',
            self::COUNSELOR => 'Guru Bimbingan Konseling',
            self::LIBRARIAN => 'Pustakawan',
            self::STUDENT => 'Peserta Didik / Siswa',
            self::PARENT => 'Wali Murid / Orang Tua',
            self::ALUMNI => 'Alumni',
            self::COMMITTEE => 'Panitia Kegiatan / PPDB',
            self::PTSP => 'Petugas TU / PTSP',
            self::RECEPTIONIST => 'Penyambut Tamu / Resepsionis',
            self::PICKET => 'Petugas Piket',
            self::SECURITY_GUARD => 'Satpam / Pos Jaga',
            self::TASK_FORCE => 'Satgas Pengaduan',
            self::SIGNATORY => 'Pejabat Penandatangan',
        };
    }

    /**
     * Whether this role represents internal school staff/officers.
     */
    public function isStaff(): bool
    {
        return ! in_array($this, [self::STUDENT, self::PARENT, self::ALUMNI], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}

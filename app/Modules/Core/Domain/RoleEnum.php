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
        };
    }
}

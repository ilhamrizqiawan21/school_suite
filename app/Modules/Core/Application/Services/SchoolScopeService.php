<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Infrastructure\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

class SchoolScopeService
{
    /** @return array{id:int, name:?string, level:?string, address:?string, phone:?string, email:?string, logo_path:?string, theme:array<string, mixed>} */
    public function getPrimaryPublicSnapshot(): array
    {
        $school = School::query()->orderBy('id')->firstOrFail();

        return [
            'id' => (int) $school->id,
            'name' => $school->name,
            'level' => $school->level,
            'address' => $school->address,
            'phone' => $school->phone,
            'email' => $school->email,
            'logo_path' => $school->logo_path,
            'theme' => $school->publicTheme(),
        ];
    }

    /** Lock a school row to serialize school-scoped sequences inside a transaction. */
    public function lockSchool(int $schoolId): void
    {
        abort_unless(School::query()->whereKey($schoolId)->lockForUpdate()->exists(), 404);
    }

    /** @return array{id:int, name:string, legal_name:?string, npsn:?string, address:?string, principal_name:?string} */
    public function getSchoolDetails(int $schoolId): array
    {
        $school = School::query()->findOrFail($schoolId);

        return [
            'id' => (int) $school->id,
            'name' => (string) $school->name,
            'legal_name' => $school->legal_name,
            'npsn' => $school->npsn,
            'address' => $school->address,
            'principal_name' => $school->principal_name,
        ];
    }

    /** @return array{name:?string, legal_name:?string, npsn:?string, address:?string, phone:?string, email:?string, website:?string, level:?string, school_status:?string, accreditation:?string, principal_name:?string} */
    public function getSchoolLegalSnapshot(int $schoolId): array
    {
        $school = School::query()->findOrFail($schoolId);
        $snapshot = $school->only(['name', 'legal_name', 'npsn', 'address', 'phone', 'email', 'website', 'level', 'school_status', 'accreditation', 'principal_name']);
        $letterhead = data_get($school->settings, 'letterhead', []);

        return [
            ...$snapshot,
            'parent_institution' => data_get($letterhead, 'parent_institution'),
            'city' => data_get($letterhead, 'city'),
            'postal_code' => data_get($letterhead, 'postal_code'),
        ];
    }

    public function getSchoolLogoDataUri(int $schoolId): ?string
    {
        $school = School::query()->findOrFail($schoolId);
        if (! $school->logo_path || ! Storage::disk('local')->exists($school->logo_path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($school->logo_path) ?: 'image/png';

        // Dompdf relies on PHP GD for PNG decoding. A missing GD extension
        // must not make every outgoing-letter PDF endpoint return HTTP 500.
        // JPEG/SVG logos remain available; production should still enable GD
        // when schools need PNG logos rendered inside PDFs.
        if ($mime === 'image/png' && ! extension_loaded('gd')) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($school->logo_path));
    }

    /**
     * Resolve configured IANA timezone for a school, defaulting to Asia/Jakarta.
     */
    public function getTimezone(int $schoolId): string
    {
        $timezone = School::query()->whereKey($schoolId)->value('timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'Asia/Jakarta';
    }

    /**
     * Current immutable timestamp in the school's local timezone.
     */
    public function localNow(int $schoolId): CarbonImmutable
    {
        return CarbonImmutable::now($this->getTimezone($schoolId));
    }
}

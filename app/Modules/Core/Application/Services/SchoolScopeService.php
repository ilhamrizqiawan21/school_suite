<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Infrastructure\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SchoolScopeService
{
    public function usesMadrasahPublicTheme(string $npsn): bool
    {
        return config('public_portal.theme') === 'madrasah' || (app()->isLocal() && $npsn === '20109988');
    }

    public function primaryPublicTheme(): ?string
    {
        $npsn = (string) School::query()->orderBy('id')->value('npsn');

        return $this->usesMadrasahPublicTheme($npsn) ? 'madrasah' : null;
    }

    /** @return ?array{id:int, name:?string, level:?string, address:?string, phone:?string, email:?string, logo_path:?string, service_hours:?string, content_blocks:array<string, mixed>, theme:array<string, mixed>} */
    public function findPrimaryPublicSnapshot(): ?array
    {
        if (! School::query()->exists()) {
            return null;
        }

        return $this->getPrimaryPublicSnapshot();
    }

    public function publicServiceAvailability(string $code): array
    {
        $school = School::query()->orderBy('id')->first();
        $config = data_get($school?->settings, 'service_catalog.'.$code, []);

        return ['available' => (bool) ($config['available'] ?? true),
            'message' => trim((string) ($config['maintenance_message'] ?? '')) ?: 'Layanan sedang ditutup untuk pengajuan baru. Permohonan yang sudah masuk tetap dapat dilacak dan ditindaklanjuti.'];
    }

    public function publicContentForRequest(School $school, bool $draft = false): array
    {
        if (request()->boolean('preview_draft')) {
            abort_unless(request()->user() !== null && Gate::forUser(request()->user())->allows('update', $school), 403);
            $draft = true;
        }
        $blocks = $draft ? $school->publicContentRegistry()['draft'] : $school->publishedPublicContentBlocks();
        $pages = [
            '' => ['copy.vue_school_home', 'copy.blade_home'],
            'profil-sekolah' => ['copy.vue_school_profile', 'copy.blade_profile'],
            'akademik-fasilitas' => ['copy.vue_school_programs'],
            'kesiswaan-prestasi' => ['copy.vue_school_studentlife'],
            'prestasi' => ['copy.vue_school_achievements', 'copy.blade_achievements'],
            'struktur-organisasi' => ['copy.vue_organization_index'],
            'direktori-gtk' => ['copy.vue_directory_teachers', 'copy.blade_teachers'],
            'kontak' => ['copy.vue_school_contact'],
            'berita' => ['copy.vue_news_index', 'copy.vue_news_show', 'copy.blade_news'],
            'galeri' => ['copy.vue_gallery_index', 'copy.blade_gallery'],
            'galeri/video' => ['copy.blade_videos'],
            'pusat-unduh' => ['copy.vue_downloads_index'],
            'zona-integritas' => ['copy.vue_integrity_index'],
            'jadwal-salat' => ['copy.vue_prayer_index'],
            'e-layanan' => ['copy.vue_services_index'],
            'data-siswa' => ['copy.vue_students_index'],
            'layanan/ptsp' => ['copy.vue_services_ptsp', 'copy.vue_services_ptspreceipt', 'copy.vue_services_ptsprevision'],
            'layanan/ptsp/akun' => ['copy.vue_services_ptspaccount'],
            'layanan/surat-dinas' => ['copy.vue_services_externalletter', 'copy.vue_services_externalletterreceipt'],
            'layanan/buku-tamu' => ['copy.vue_services_guestbook'],
            'layanan/izin-siswa' => ['copy.vue_services_studentpermit'],
            'layanan/mutasi' => ['copy.vue_services_mutation'],
            'layanan/kelulusan' => ['copy.vue_services_graduation'],
            'layanan/legalisir' => ['copy.vue_services_legalization'],
            'layanan/surat-kuasa' => ['copy.vue_services_powerofattorney'],
            'layanan/lapor' => ['copy.vue_services_complaint'],
            'lapor/transparansi' => ['copy.vue_services_complainttransparency'],
            'lacak' => ['copy.vue_tracking_index'],
            'alumni' => ['copy.vue_alumni_home'],
            'alumni/direktori' => ['copy.vue_alumni_directory'],
            'layanan/tracer-alumni' => ['copy.vue_alumni_portal'],
            'perpustakaan' => ['copy.blade_perpustakaan', 'copy.blade_library_book_card'],
            'perpustakaan/portal' => ['copy.vue_library_index'],
            'ppdb' => ['copy.vue_admissions_index'],
            'verifikasi' => ['copy.vue_verification_index'],
        ];
        $path = trim(request()->path(), '/');
        $selected = $pages[$path] ?? [];
        if ($selected === []) {
            foreach (array_reverse($pages, true) as $prefix => $keys) {
                if ($prefix !== '' && str_starts_with($path, $prefix.'/')) {
                    $selected = $keys;
                    break;
                }
            }
        }
        $allowed = [...$selected, 'copy.vue_publicheader', 'copy.vue_publicsectionlayout', 'copy.blade_navigation', 'copy.blade_footer'];

        $seoKey = 'seo.'.str_replace('copy.', '', $selected[0] ?? '');
        $filtered = array_filter($blocks, static fn ($blockId) => (! str_starts_with($blockId, 'copy.') || in_array($blockId, $allowed, true))
            && ! str_starts_with($blockId, 'seo.'), ARRAY_FILTER_USE_KEY);
        $filtered['seo.current'] = $blocks[$seoKey] ?? [];

        return $filtered;
    }

    /** @return array{id:int, name:?string, level:?string, address:?string, phone:?string, email:?string, logo_path:?string, service_hours:?string, content_blocks:array<string, mixed>, theme:array<string, mixed>} */
    public function getPrimaryPublicSnapshot(): array
    {
        $school = School::query()->orderBy('id')->firstOrFail();
        $registry = $school->publicContentRegistry();

        return [
            'id' => (int) $school->id,
            'name' => $school->name,
            'level' => $school->level,
            'address' => $school->address,
            'phone' => $school->phone,
            'email' => $school->email,
            'logo_path' => $school->logo_path,
            'logo_url' => $school->publicBrandingUrl('logo'),
            'favicon_url' => $school->publicBrandingUrl('favicon'),
            'timezone' => $school->timezone ?: config('app.timezone'),
            'service_hours' => data_get($school->settings, 'service_hours'),
            'content_blocks' => $this->publicContentForRequest($school),
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

    /**
     * Return operational rule configuration for permits, kiosk, and post gates (K-03, K-04 defaults).
     *
     * @return array{permit_same_day_cutoff: string, permit_max_advance_days: int, kiosk_dedup_seconds: int, has_security_post_pin: bool}
     */
    public function getOperationsSettings(int $schoolId): array
    {
        $school = School::query()->findOrFail($schoolId);
        $ops = data_get($school->settings, 'operations', []);

        return [
            'permit_same_day_cutoff' => (string) ($ops['permit_same_day_cutoff'] ?? '09:00'),
            'permit_max_advance_days' => max(1, (int) ($ops['permit_max_advance_days'] ?? 30)),
            'kiosk_dedup_seconds' => max(10, (int) ($ops['kiosk_dedup_seconds'] ?? 120)),
            'has_security_post_pin' => ! empty($ops['security_post_pin_hash']) || ! empty($ops['security_post_pin']),
        ];
    }

    public function verifySecurityPostPin(int $schoolId, string $pin): bool
    {
        $candidate = trim($pin);
        if ($candidate === '') {
            return false;
        }

        $school = School::query()->findOrFail($schoolId);
        $ops = data_get($school->settings, 'operations', []);
        $hash = $ops['security_post_pin_hash'] ?? null;

        if (is_string($hash) && $hash !== '') {
            return Hash::check($candidate, $hash);
        }

        $plain = $ops['security_post_pin'] ?? null;
        if (is_string($plain) && $plain !== '') {
            return hash_equals($plain, $candidate);
        }

        return false;
    }

    public function setSecurityPostPin(int $schoolId, string $pin): void
    {
        $school = School::query()->findOrFail($schoolId);
        $settings = is_array($school->settings) ? $school->settings : [];
        $ops = is_array($settings['operations'] ?? null) ? $settings['operations'] : [];
        $ops['security_post_pin_hash'] = Hash::make(trim($pin));
        unset($ops['security_post_pin']);
        $settings['operations'] = $ops;

        $school->forceFill(['settings' => $settings])->save();
    }
}

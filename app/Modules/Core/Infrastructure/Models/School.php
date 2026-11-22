<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    private const PUBLIC_THEME_COLORS = ['#3157a4', '#277450', '#7255ae', '#b87818', '#b64a42'];

    /** @var array<string, array{label: string, description: string, primary: string, accent: string, surface: string, ink: string, muted: string}> */
    private const PUBLIC_THEME_PRESETS = [
        'editorial' => [
            'label' => 'Editorial',
            'description' => 'Hangat, berkarakter, dan cocok untuk cerita sekolah.',
            'primary' => '#3157a4',
            'accent' => '#8a661f',
            'surface' => '#f5f8fb',
            'ink' => '#173044',
            'muted' => '#526673',
        ],
        'institutional' => [
            'label' => 'Institutional',
            'description' => 'Tenang, tepercaya, dan terasa lebih formal.',
            'primary' => '#277450',
            'accent' => '#765716',
            'surface' => '#f4faf7',
            'ink' => '#18352b',
            'muted' => '#557067',
        ],
        'campus' => [
            'label' => 'Campus',
            'description' => 'Segar, kreatif, dan lebih ekspresif untuk komunitas belajar.',
            'primary' => '#7255ae',
            'accent' => '#795725',
            'surface' => '#f8f7fc',
            'ink' => '#281d3f',
            'muted' => '#655b72',
        ],
    ];

    private const PUBLIC_HERO_VARIANTS = [
        'collage' => 'Kolase foto',
        'single' => 'Satu foto fokus',
        'minimal' => 'Minimal dengan overlay',
    ];

    protected $table = 'schools';

    protected $fillable = [
        'name',
        'legal_name',
        'npsn',
        'level',
        'school_status',
        'accreditation',
        'address',
        'phone',
        'email',
        'principal_name',
        'website',
        'timezone',
        'vision',
        'mission',
        'about',
        'principal_message',
        'logo_path',
        'favicon_path',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /** @return list<array{value: string, label: string, description: string}> */
    public static function publicThemePresetOptions(): array
    {
        $options = [];

        foreach (self::PUBLIC_THEME_PRESETS as $value => $preset) {
            $options[] = [
                'value' => $value,
                'label' => $preset['label'],
                'description' => $preset['description'],
            ];
        }

        return $options;
    }

    /** @return list<array{value: string, label: string}> */
    public static function publicHeroVariantOptions(): array
    {
        return array_map(
            static fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(self::PUBLIC_HERO_VARIANTS),
            self::PUBLIC_HERO_VARIANTS
        );
    }

    /** @return array<string, mixed> */
    public function publicTheme(): array
    {
        $settings = $this->settings ?? [];
        $publicTheme = data_get($settings, 'public_theme', []);
        $selectedPreset = data_get($publicTheme, 'preset');
        $preset = is_string($selectedPreset) && array_key_exists($selectedPreset, self::PUBLIC_THEME_PRESETS)
            ? $selectedPreset
            : 'editorial';
        $theme = self::PUBLIC_THEME_PRESETS[$preset];
        $legacyPrimary = data_get($settings, 'theme_primary');

        // The school's identity color is the source of truth for the public
        // primary and accent colors, even when a public visual preset is selected.
        // Presets still supply the supporting surface, ink, and muted tones.
        if (in_array($legacyPrimary, self::PUBLIC_THEME_COLORS, true)) {
            $theme['primary'] = $legacyPrimary;
        }

        $theme['accent'] = $theme['primary'];

        $heroVariant = data_get($publicTheme, 'hero_variant');
        $hero = static fn (string $key): ?string => is_string(data_get($publicTheme, $key)) && trim((string) data_get($publicTheme, $key)) !== ''
            ? trim((string) data_get($publicTheme, $key))
            : null;

        return [
            'preset' => $preset,
            'primary' => $theme['primary'],
            'accent' => $theme['accent'],
            'surface' => $theme['surface'],
            'ink' => $theme['ink'],
            'muted' => $theme['muted'],
            'hero_variant' => is_string($heroVariant) && array_key_exists($heroVariant, self::PUBLIC_HERO_VARIANTS)
                ? $heroVariant
                : 'collage',
            'sections' => [
                'explore' => data_get($publicTheme, 'show_explore', true) !== false,
                'news' => data_get($publicTheme, 'show_news', true) !== false,
                'gallery' => data_get($publicTheme, 'show_gallery', true) !== false,
            ],
            'hero' => [
                'title' => $hero('hero_title'),
                'accent' => $hero('hero_accent'),
                'primary_label' => $hero('hero_primary_label'),
                'secondary_label' => $hero('hero_secondary_label'),
            ],
        ];
    }

    /**
     * Return canonical public content blocks with draft/published separation,
     * stable section IDs, and revision history (AP-04.01..AP-04.18).
     *
     * @return array{
     *     published: array<string, array<string, mixed>>,
     *     draft: array<string, array<string, mixed>>,
     *     revisions: list<array<string, mixed>>
     * }
     */
    public function publicContentRegistry(): array
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $content = is_array($settings['public_content'] ?? null) ? $settings['public_content'] : [];
        $defaults = self::defaultPublicContentBlocks($this);

        $published = array_replace_recursive($defaults, is_array($content['published'] ?? null) ? $content['published'] : []);
        $draft = array_replace_recursive($published, is_array($content['draft'] ?? null) ? $content['draft'] : []);
        $revisions = is_array($content['revisions'] ?? null) ? array_values($content['revisions']) : [];

        return [
            'published' => $published,
            'draft' => $draft,
            'revision' => (int) ($content['revision'] ?? 0),
            'revisions' => $revisions,
        ];
    }

    /**
     * Default structured content blocks with stable IDs for public pages (AP-04.02, AP-04.12).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaultPublicContentBlocks(?self $school = null): array
    {
        $schoolName = $school?->name ?: 'Madrasah';
        $accreditation = $school?->accreditation ?: 'A';

        return [
            'home.hero' => [
                'id' => 'home.hero',
                'visible' => true,
                'badge_primary' => 'Madrasah Aliyah Negeri Riset & Adiwiyata Mandiri',
                'badge_secondary' => 'Zona Integritas (WBK)',
                'headline_prefix' => 'Mencetak Generasi',
                'headline_highlight' => 'Qur’ani, Unggul Riset',
                'headline_suffix' => ', dan Berintegritas',
                'description' => sprintf(
                    'Selamat datang di Portal Resmi %s. Lembaga pendidikan Islam modern terakreditasi "%s" yang menghadirkan integrasi kedalaman ilmu syar\'i, sains teknologi abad 21, serta layanan digital terpadu.',
                    $schoolName,
                    $accreditation,
                ),
                'cta_ppdb_label' => 'Daftar PPDB',
                'cta_ptsp_label' => 'PTSP Online',
                'cta_lapor_label' => 'Siap Lapor',
                'cta_lacak_label' => 'Lacak Berkas',
            ],
            'home.welcome' => [
                'id' => 'home.welcome',
                'visible' => true,
                'badge' => 'Sambutan Kepala Madrasah',
                'title' => 'Meneguhkan Eksistensi Madrasah Unggul: Berakar pada Nilai Keislaman, Bertumbuh dalam Sains & Teknologi, dan Mendunia dengan Karakter Mulia',
                'greeting' => 'Assalamu\'alaikum Warahmatullahi Wabarakatuh.',
                'lead' => 'Selamat datang di portal resmi madrasah.',
            ],
            'home.zi' => [
                'id' => 'home.zi',
                'visible' => true,
                'badge' => 'MENUJU WILAYAH BEBAS DARI KORUPSI (WBK & WBBM)',
                'title_prefix' => 'Komitmen Pelayanan Bersih,',
                'title_highlight' => 'Transparan & Berintegritas',
                'subtitle' => sprintf(
                    '%s berkomitmen menyelenggarakan tata kelola madrasah yang bebas dari korupsi, kolusi, nepotisme, dan gratifikasi serta memberikan pelayanan publik yang prima dan ramah bagi seluruh santri, wali murid, dan masyarakat.',
                    $schoolName,
                ),
            ],
            'home.services' => [
                'id' => 'home.services',
                'visible' => true,
                'eyebrow' => 'TRANSFORMASI DIGITAL KEMENAG',
                'title' => 'Layanan Terpadu Satu Pintu (PTSP)',
                'subtitle' => 'Permudah pengurusan administrasi siswa, alumni, dan orang tua secara daring tanpa antre.',
            ],
            'home.programs' => [
                'id' => 'home.programs',
                'visible' => true,
                'eyebrow' => 'Pilihan Konsentrasi Belajar',
                'title' => 'Peminatan & Kurikulum Merdeka',
                'subtitle' => 'Memberikan ruang eksplorasi bakat, minat, dan proyek penguatan profil pelajar rahmatan lil \'alamin.',
            ],
            'home.facilities' => [
                'id' => 'home.facilities',
                'visible' => true,
                'eyebrow' => 'Lingkungan Belajar Asri',
                'title' => 'Sarana & Prasarana Madrasah',
            ],
            'home.news' => [
                'id' => 'home.news',
                'visible' => true,
                'eyebrow' => 'Kabar Madrasah',
                'title' => 'Warta & Agenda Terkini',
            ],
            'home.achievements' => [
                'id' => 'home.achievements',
                'visible' => true,
                'eyebrow' => 'Tradisi Juara Siswa Banua',
                'title' => 'Prestasi Gemilang Siswa',
                'subtitle' => 'Capaian membanggakan siswa dalam bidang sains riset, seni kaligrafi, tilawah Al-Qur\'an, olahraga, dan keagamaan.',
            ],
            'home.gallery' => [
                'id' => 'home.gallery',
                'visible' => true,
                'eyebrow' => 'Ilustrasi Aktivitas',
                'title' => 'Galeri & Kegiatan Madrasah',
                'subtitle' => 'Kilas balik suasana belajar, pembiasaan religius, dan kegiatan kesiswaan santri & siswa.',
            ],
            'home.ppdb' => [
                'id' => 'home.ppdb',
                'visible' => true,
                'badge' => 'Penerimaan Peserta Didik Baru',
                'title' => sprintf('Terima Kasih atas Antusiasme & Kepercayaan Menuju %s', $schoolName),
                'subtitle' => 'Lihat jadwal dan jalur pendaftaran, pantau hasil seleksi, atau periksa informasi daftar ulang di portal PPDB.',
            ],
            'home.contact' => [
                'id' => 'home.contact',
                'visible' => true,
                'eyebrow' => 'Hubungi Kami',
                'subtitle' => 'Kami siap melayani kebutuhan informasi pendidikan putra-putri Anda. Silakan berkunjung pada hari dan jam kerja madrasah.',
            ],
            'profile.values' => [
                'id' => 'profile.values',
                'visible' => true,
                'title' => '5 Nilai Budaya Kerja Kementerian Agama',
                'subtitle' => sprintf('Pondasi karakter dan integritas seluruh aparatur, pendidik, dan tenaga kependidikan %s.', $schoolName),
            ],
            'profile.pillars' => [
                'id' => 'profile.pillars',
                'visible' => true,
                'title' => sprintf('4 Pilar Keunggulan %s', $schoolName),
            ],
            'shell.options' => [
                'id' => 'shell.options',
                'visible' => true,
                'hide_demo_fallbacks' => false,
                'ptsp_ticker_text' => 'Kini pengurusan permohonan surat keterangan aktif, legalisir, mutasi siswa, dan rekomendasi beasiswa dapat diajukan secara online melalui portal PTSP.',
            ],
            'shell.footer' => [
                'id' => 'shell.footer',
                'visible' => true,
                'tagline' => 'Madrasah Aliyah Negeri unggulan yang mengedepankan pendidikan berbasis adiwiyata, karakter islami, dan riset sains teknologi modern.',
                'copyright_notice' => 'Hak Cipta Dilindungi Undang-Undang.',
            ],
            'ptsp.hero' => [
                'id' => 'ptsp.hero',
                'visible' => true,
                'label' => 'PTSP Digital & Layanan Akademik',
                'title' => sprintf('Pelayanan Terpadu Satu Pintu %s', $schoolName),
                'subtitle' => 'Pengajuan surat keterangan, rekomendasi, serta layanan administrasi masyarakat & wali siswa secara terpadu.',
                'about_title' => 'Sekilas Tentang PTSP',
                'about_description' => sprintf(
                    'PTSP (Pelayanan Terpadu Satu Pintu) adalah pusat layanan administrasi dan persuratan resmi %s yang menyatukan permohonan surat keterangan siswa, wali murid, alumni, dan masyarakat dalam satu pintu dengan nomor resi dan pelacakan transparan.',
                    $schoolName,
                ),
            ],
            'external_letter.hero' => [
                'id' => 'external_letter.hero',
                'visible' => true,
                'badge' => 'Agenda Persuratan Tata Usaha',
                'title' => 'Layanan Penerimaan',
                'highlight' => 'Surat Dinas Eksternal',
                'lead' => sprintf(
                    'Saluran resmi penyampaian surat dinas, undangan, permohonan kerja sama, maupun pemberitahuan dari instansi pemerintah, swasta, dan mitra masyarakat ke Tata Usaha %s.',
                    $schoolName,
                ),
                'standard_badge' => 'Standar Pelayanan: Konfirmasi Penerimaan & Disposisi 1–2 Hari Kerja',
            ],
            'guestbook.hero' => [
                'id' => 'guestbook.hero',
                'visible' => true,
                'label' => 'Resepsionis & Buku Tamu Digital',
                'greeting' => 'Ahlan wa Sahlan / Selamat Datang',
                'title' => sprintf('Buku Tamu Digital %s', $schoolName),
                'description' => 'Silakan isi formulir registrasi kunjungan di bawah ini sebelum menuju ruang layanan atau menemui pejabat/pendidik di lingkungan sekolah.',
                'reception_hours' => data_get($school?->settings, 'service_hours') ?: 'Senin–Jumat, 07.30–15.00',
            ],
            'student_permit.hero' => [
                'id' => 'student_permit.hero',
                'visible' => true,
                'badge' => 'Layanan Resmi Perizinan Siswa',
                'title' => 'Surat Izin Siswa Online',
                'academic_year_label' => 'TA 2026/2027',
                'description' => 'Layanan permohonan izin tidak masuk sekolah (Sakit / Kepentingan Keluarga) resmi yang diajukan oleh Orang Tua / Wali Siswa secara online, cepat, dan disertai tanda tangan digital sah.',
            ],
            'mutation.hero' => [
                'id' => 'mutation.hero',
                'visible' => true,
                'badge' => 'Layanan Mutasi Siswa Online',
                'academic_year_label' => 'Tahun Pelajaran 2026/2027',
                'description' => 'Informasi alur prosedur & formulir permohonan mutasi masuk dan mutasi keluar siswa secara online.',
            ],
            'graduation.hero' => [
                'id' => 'graduation.hero',
                'visible' => true,
                'badge' => 'Pengumuman Kelulusan Resmi',
                'academic_year_label' => '2026/2027',
                'description' => 'Layanan resmi pengecekan status kelulusan peserta didik dan pengunduhan Surat Keterangan Lulus (SKL) digital mandiri secara online.',
            ],
            'legalization.hero' => [
                'id' => 'legalization.hero',
                'visible' => true,
                'badge' => 'Layanan Legalisir Ijazah Online',
                'academic_year_label' => 'Tahun Pelajaran 2026/2027',
                'description' => 'Formulir Permohonan Legalisir, Pembuatan Surat Kuasa & SPTJM dengan Tanda Tangan Digital Langsung secara online.',
            ],
            'complaint.hero' => [
                'id' => 'complaint.hero',
                'visible' => true,
                'pill_label' => 'Whistleblowing & Layanan Aspirasi Terpadu',
                'pill_accent' => '100% Bebas Intimidasi',
                'headline' => 'Suara Anda Lindungi Madrasah.',
                'headline_highlight' => 'Laporkan Kendala & Sampaikan Ide.',
                'lead' => sprintf(
                    'Saluran pengaduan resmi %s. Tangani perundungan, sarana belajar, ketertiban, dan layanan madrasah secara aman dengan opsi Kirim Sebagai Anonim.',
                    $schoolName,
                ),
            ],
            'alumni.hero' => [
                'id' => 'alumni.hero',
                'visible' => true,
                'badge' => 'OFFICIAL ALUMNI NETWORK & CAREER HUB',
                'title' => 'Temu Silaturahmi,',
                'highlight' => 'Keluarga Besar Alumni',
                'description' => sprintf(
                    'Wadah resmi penelusuran lulusan (tracer study), jejaring karir profesional, peluang beasiswa perguruan tinggi, bursa kerja aktif, dan kontribusi nyata bagi almamater tercinta %s.',
                    $schoolName,
                ),
            ],
            'admissions.hero' => [
                'id' => 'admissions.hero',
                'visible' => true,
                'open_badge' => 'Pendaftaran dibuka',
                'closed_badge' => 'Pendaftaran belum dibuka',
                'open_title' => 'Penerimaan siswa baru sedang dibuka.',
                'closed_title' => 'Penerimaan siswa baru belum dibuka.',
                'period_eyebrow' => 'Informasi SPMB',
                'period_title' => 'Periode dan jalur penerimaan.',
            ],
            'tracking.hero' => [
                'id' => 'tracking.hero',
                'visible' => true,
                'label' => 'PUSAT LAYANAN INFORMASI & PELACAKAN RESMI',
                'title' => 'Pusat Lacak Status Layanan Terpadu',
                'subtitle' => 'Lacak progres seluruh permohonan online Anda secara transparan dan realtime dengan memasukkan Nomor Tiket atau Kode Registrasi Pengajuan beserta PIN Verifikasi.',
                'keywords_hint' => 'No. Tiket • Kode Resi • PIN Verifikasi',
                'placeholder' => 'Contoh: PTSP-2026..., IZN-2026..., MUT-2026..., LEG-2026..., ADU-2026...',
            ],
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'school_id');
    }

    /**
     * @return HasMany<SchoolGalleryPhoto, $this>
     */
    public function galleryPhotos(): HasMany
    {
        return $this->hasMany(SchoolGalleryPhoto::class, 'school_id')->orderBy('sort_order')->orderByDesc('created_at');
    }

    /** @return HasMany<SchoolGalleryAlbum, $this> */
    public function galleryAlbums(): HasMany
    {
        return $this->hasMany(SchoolGalleryAlbum::class, 'school_id')->orderBy('sort_order')->orderByDesc('created_at');
    }

    /** @return HasMany<SchoolOrganizationMember, $this> */
    public function organizationMembers(): HasMany
    {
        return $this->hasMany(SchoolOrganizationMember::class, 'school_id')->orderBy('sort_order')->orderBy('name');
    }
}

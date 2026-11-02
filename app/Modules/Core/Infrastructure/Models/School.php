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
        // primary color, even when a public visual preset is selected. The
        // preset still supplies the supporting accent, surface, and ink.
        if (in_array($legacyPrimary, self::PUBLIC_THEME_COLORS, true)) {
            $theme['primary'] = $legacyPrimary;
        }

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

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

    /** @return array{primary: string} */
    public function publicTheme(): array
    {
        $primary = data_get($this->settings ?? [], 'theme_primary');

        return [
            'primary' => in_array($primary, self::PUBLIC_THEME_COLORS, true) ? $primary : '#3157a4',
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

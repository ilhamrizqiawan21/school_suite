<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtspService extends Model
{
    protected $table = 'ptsp_services';

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'category',
        'description',
        'requirements',
        'required_documents',
        'estimated_duration',
        'service_hours',
        'delivery_mode',
        'requires_verification_secret',
        'is_active',
        'is_published',
        'sort_order',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'requirements' => 'array',
            'required_documents' => 'array',
            'requires_verification_secret' => 'boolean',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /**
     * @return HasMany<PtspRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(PtspRequest::class, 'ptsp_service_id');
    }
}

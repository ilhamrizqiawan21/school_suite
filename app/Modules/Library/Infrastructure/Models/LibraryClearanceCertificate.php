<?php

declare(strict_types=1);

namespace App\Modules\Library\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryClearanceCertificate extends Model
{
    protected $table = 'library_clearance_certificates';

    protected $fillable = [
        'school_id',
        'student_id',
        'service_ticket_id',
        'clearance_code',
        'status',
        'active_loan_count',
        'clearance_document_id',
        'issued_by',
        'issued_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'service_ticket_id' => 'integer',
            'active_loan_count' => 'integer',
            'clearance_document_id' => 'integer',
            'issued_by' => 'integer',
            'issued_at' => 'datetime',
        ];
    }
}

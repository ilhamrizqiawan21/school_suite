<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Homeroom\Infrastructure\Models\AssistanceRecord;
use App\Modules\Homeroom\Infrastructure\Models\StudentAchievement;
use App\Modules\Homeroom\Infrastructure\Models\StudentIncident;
use App\Shared\Audit\AuditLogger;

class StudentDisciplineService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Mencatat pelanggaran tata tertib / insiden kesiswaan.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordIncident(array $data, User $actor): StudentIncident
    {
        $data['school_id'] = (int) $actor->school_id;
        $data['recorded_by_user_id'] = $actor->id;

        $incident = StudentIncident::create($data);

        $this->auditLogger->log(
            eventType: 'homeroom.incident.recorded',
            targetType: 'student_incident',
            targetId: (string) $incident->id,
            newValues: [
                'student_id' => $incident->student_id,
                'title' => $incident->title,
                'penalty_points' => $incident->penalty_points,
            ],
            oldValues: null,
            actor: $actor,
            schoolId: $actor->school_id
        );

        return $incident;
    }

    /**
     * Mencatat prestasi siswa.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordAchievement(array $data, User $actor): StudentAchievement
    {
        $data['school_id'] = (int) $actor->school_id;

        $achievement = StudentAchievement::create($data);

        $this->auditLogger->log(
            eventType: 'homeroom.achievement.recorded',
            targetType: 'student_achievement',
            targetId: (string) $achievement->id,
            newValues: [
                'student_id' => $achievement->student_id,
                'title' => $achievement->title,
                'rank' => $achievement->rank,
                'level' => $achievement->level,
            ],
            oldValues: null,
            actor: $actor,
            schoolId: $actor->school_id
        );

        return $achievement;
    }

    /**
     * Mencatat tindak lanjut konseling / bantuan / home visit.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordAssistance(array $data, User $actor): AssistanceRecord
    {
        $data['school_id'] = (int) $actor->school_id;
        $data['facilitator_id'] = $actor->id;

        $record = AssistanceRecord::create($data);

        $this->auditLogger->log(
            eventType: 'homeroom.assistance.recorded',
            targetType: 'assistance_record',
            targetId: (string) $record->id,
            newValues: [
                'student_id' => $record->student_id,
                'type' => $record->type,
                'access_level' => $record->access_level,
            ],
            oldValues: null,
            actor: $actor,
            schoolId: $actor->school_id
        );

        return $record;
    }
}

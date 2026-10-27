<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Models\User;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AssessmentAccessService
{
    public function classBelongsToSchool(int $schoolId, int $termId, int $classGroupId): bool
    {
        return DB::table('class_groups')
            ->join('academic_years', 'academic_years.id', '=', 'class_groups.academic_year_id')
            ->join('terms', 'terms.academic_year_id', '=', 'academic_years.id')
            ->where('class_groups.id', $classGroupId)
            ->where('terms.id', $termId)
            ->where('class_groups.school_id', $schoolId)
            ->where('academic_years.school_id', $schoolId)
            ->exists();
    }

    public function canPublishClass(User $actor, int $termId, int $classGroupId): bool
    {
        if (! $actor->is_active || ! $this->classBelongsToSchool((int) $actor->school_id, $termId, $classGroupId)) {
            return false;
        }

        return $actor->hasRole('admin') || ($actor->hasRole('homeroom') && $this->isHomeroom($actor, $classGroupId));
    }

    public function canViewClass(User $actor, int $termId, int $classGroupId): bool
    {
        if (! $actor->is_active || ! $this->classBelongsToSchool((int) $actor->school_id, $termId, $classGroupId)) {
            return false;
        }
        if ($actor->hasRole(['admin', 'operator', 'principal'])) {
            return true;
        }
        if (! $actor->hasRole(['teacher', 'homeroom'])) {
            return false;
        }

        return ($actor->hasRole('homeroom') && $this->isHomeroom($actor, $classGroupId))
            || $this->assignmentsFor($actor, $termId, $classGroupId)->exists();
    }

    public function canInputComponent(User $actor, AssessmentComponent $component, int $termId, int $classGroupId): bool
    {
        if (! $actor->is_active || ! $actor->hasRole(['admin', 'operator', 'teacher', 'homeroom'])
            || (int) $component->scheme->school_id !== (int) $actor->school_id
            || ! $component->scheme->is_active
            || ! $this->classBelongsToSchool((int) $actor->school_id, $termId, $classGroupId)) {
            return false;
        }

        $assignments = DB::table('teaching_assignments')->where('term_id', $termId)->where('class_group_id', $classGroupId);
        if ($component->teaching_assignment_id !== null) {
            $assignments->where('id', $component->teaching_assignment_id);
        } elseif ((clone $assignments)->count() !== 1) {
            // Legacy scores have no subject key: never reuse them across multiple subjects.
            return false;
        }
        if (! $actor->hasRole(['admin', 'operator'])) {
            $assignments->whereIn('teacher_id', DB::table('teachers')->where('school_id', $actor->school_id)->where('user_id', $actor->id)->where('is_active', true)->select('id'));
        }

        return $assignments->exists();
    }

    private function isHomeroom(User $actor, int $classGroupId): bool
    {
        return DB::table('class_groups')
            ->join('teachers', 'teachers.id', '=', 'class_groups.homeroom_teacher_id')
            ->where('class_groups.id', $classGroupId)
            ->where('teachers.school_id', $actor->school_id)
            ->where('teachers.user_id', $actor->id)
            ->where('teachers.is_active', true)
            ->exists();
    }

    private function assignmentsFor(User $actor, int $termId, int $classGroupId): Builder
    {
        return DB::table('teaching_assignments')
            ->join('teachers', 'teachers.id', '=', 'teaching_assignments.teacher_id')
            ->where('teaching_assignments.term_id', $termId)
            ->where('teaching_assignments.class_group_id', $classGroupId)
            ->where('teachers.school_id', $actor->school_id)
            ->where('teachers.user_id', $actor->id)
            ->where('teachers.is_active', true);
    }
}

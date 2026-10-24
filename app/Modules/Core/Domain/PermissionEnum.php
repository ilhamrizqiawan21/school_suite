<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

enum PermissionEnum: string
{
    // Core Permissions
    case USERS_VIEW = 'core.users.view';
    case USERS_CREATE = 'core.users.create';
    case USERS_UPDATE = 'core.users.update';
    case USERS_DELETE = 'core.users.delete';
    case ROLES_MANAGE = 'core.roles.manage';
    case SCHOOL_VIEW = 'core.school.view';
    case SCHOOL_UPDATE = 'core.school.update';
    case AUDIT_VIEW = 'core.audit.view';

    // Academic Permissions
    case ACADEMIC_PERIOD_MANAGE = 'academic.period.manage';
    case ACADEMIC_CLASSES_MANAGE = 'academic.classes.manage';
    case ACADEMIC_STUDENTS_MANAGE = 'academic.students.manage';
    case ACADEMIC_SCHEDULE_MANAGE = 'academic.schedule.manage';
    case ACADEMIC_ATTENDANCE_RECORD = 'academic.attendance.record';

    // Homeroom Permissions
    case HOMEROOM_NOTES_VIEW = 'homeroom.notes.view';
    case HOMEROOM_NOTES_MANAGE = 'homeroom.notes.manage';
    case HOMEROOM_DISCIPLINE_MANAGE = 'homeroom.discipline.manage';

    // Assessment Permissions
    case ASSESSMENT_SCORES_INPUT = 'assessment.scores.input';
    case ASSESSMENT_REPORTS_PUBLISH = 'assessment.reports.publish';
}

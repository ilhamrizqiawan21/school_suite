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

    // Parity Service & Cross-Module Permissions (M4-FND-02)
    case PORTAL_CONTENT_MANAGE = 'core.portal.manage';
    case PTSP_VIEW = 'tu.ptsp.view';
    case PTSP_PROCESS = 'tu.ptsp.process';
    case GUESTBOOK_MANAGE = 'tu.guestbook.manage';
    case PERMITS_SUBMIT = 'ops.permits.submit';
    case PERMITS_DECIDE = 'ops.permits.decide';
    case GATE_SCAN = 'ops.gate.scan';
    case GATE_VALIDATE = 'ops.gate.validate';
    case BK_CASES_MANAGE = 'ops.bk.manage';
    case BK_CASES_EXPORT = 'ops.bk.export';
    case LIFECYCLE_MUTATION_MANAGE = 'lif.mutation.manage';
    case GRADUATION_RELEASE = 'lif.graduation.release';
    case LEGALIZATION_MANAGE = 'lif.legalization.manage';
    case ALUMNI_MODERATE = 'lif.alumni.moderate';
    case ADUAN_VIEW = 'serv.aduan.view';
    case ADUAN_HANDLE = 'serv.aduan.handle';
    case LIBRARY_MANAGE = 'serv.library.manage';
    case PROPOSAL_SUBMIT = 'int.proposal.submit';
    case PROPOSAL_REVIEW = 'int.proposal.review';
    case INTEGRATION_MANAGE = 'int.integration.manage';
    case DOCUMENT_SIGN = 'core.document.sign';

    /**
     * Default permission assignments per system role.
     *
     * @return list<self>
     */
    public static function defaultForRole(RoleEnum $role): array
    {
        return match ($role) {
            RoleEnum::ADMIN => self::cases(),
            RoleEnum::OPERATOR => [
                self::USERS_VIEW,
                self::USERS_CREATE,
                self::SCHOOL_VIEW,
                self::SCHOOL_UPDATE,
                self::PORTAL_CONTENT_MANAGE,
                self::ACADEMIC_PERIOD_MANAGE,
                self::ACADEMIC_CLASSES_MANAGE,
                self::ACADEMIC_STUDENTS_MANAGE,
                self::ACADEMIC_SCHEDULE_MANAGE,
                self::PTSP_VIEW,
                self::PTSP_PROCESS,
                self::GUESTBOOK_MANAGE,
                self::LIFECYCLE_MUTATION_MANAGE,
                self::GRADUATION_RELEASE,
                self::LEGALIZATION_MANAGE,
                self::ALUMNI_MODERATE,
                self::INTEGRATION_MANAGE,
            ],
            RoleEnum::PRINCIPAL => [
                self::USERS_VIEW,
                self::SCHOOL_VIEW,
                self::AUDIT_VIEW,
                self::GRADUATION_RELEASE,
                self::PROPOSAL_REVIEW,
                self::DOCUMENT_SIGN,
            ],
            RoleEnum::TEACHER => [
                self::ACADEMIC_ATTENDANCE_RECORD,
                self::ASSESSMENT_SCORES_INPUT,
                self::PROPOSAL_SUBMIT,
            ],
            RoleEnum::HOMEROOM => [
                self::ACADEMIC_ATTENDANCE_RECORD,
                self::HOMEROOM_NOTES_VIEW,
                self::HOMEROOM_NOTES_MANAGE,
                self::HOMEROOM_DISCIPLINE_MANAGE,
                self::ASSESSMENT_SCORES_INPUT,
                self::ASSESSMENT_REPORTS_PUBLISH,
                self::PERMITS_DECIDE,
                self::PROPOSAL_SUBMIT,
            ],
            RoleEnum::COUNSELOR => [
                self::HOMEROOM_NOTES_VIEW,
                self::BK_CASES_MANAGE,
                self::BK_CASES_EXPORT,
            ],
            RoleEnum::LIBRARIAN => [
                self::LIBRARY_MANAGE,
            ],
            RoleEnum::STUDENT => [
                self::PERMITS_SUBMIT,
                self::PROPOSAL_SUBMIT,
            ],
            RoleEnum::PARENT => [
                self::PERMITS_SUBMIT,
            ],
            RoleEnum::ALUMNI => [
                self::LEGALIZATION_MANAGE,
            ],
            RoleEnum::COMMITTEE => [
                self::PROPOSAL_REVIEW,
            ],
            RoleEnum::PTSP => [
                self::PTSP_VIEW,
                self::PTSP_PROCESS,
                self::GUESTBOOK_MANAGE,
                self::LIFECYCLE_MUTATION_MANAGE,
                self::LEGALIZATION_MANAGE,
            ],
            RoleEnum::RECEPTIONIST => [
                self::GUESTBOOK_MANAGE,
            ],
            RoleEnum::PICKET => [
                self::PERMITS_DECIDE,
                self::GATE_SCAN,
            ],
            RoleEnum::SECURITY_GUARD => [
                self::GATE_VALIDATE,
            ],
            RoleEnum::TASK_FORCE => [
                self::ADUAN_VIEW,
                self::ADUAN_HANDLE,
            ],
            RoleEnum::SIGNATORY => [
                self::DOCUMENT_SIGN,
                self::GRADUATION_RELEASE,
            ],
        };
    }
}

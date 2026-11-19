<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Domain\DataClassificationMap;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ParityFoundationDomainTest extends TestCase
{
    public function test_role_enum_covers_all_17_parity_roles(): void
    {
        $this->assertCount(17, RoleEnum::cases());
        $this->assertContains('ptsp', RoleEnum::values());
        $this->assertContains('receptionist', RoleEnum::values());
        $this->assertContains('picket', RoleEnum::values());
        $this->assertContains('security_guard', RoleEnum::values());
        $this->assertContains('task_force', RoleEnum::values());
        $this->assertContains('signatory', RoleEnum::values());
        $this->assertContains('alumni', RoleEnum::values());
        $this->assertContains('committee', RoleEnum::values());
        $this->assertContains('parent', RoleEnum::values());

        $this->assertFalse(RoleEnum::STUDENT->isStaff());
        $this->assertFalse(RoleEnum::PARENT->isStaff());
        $this->assertFalse(RoleEnum::ALUMNI->isStaff());
        $this->assertTrue(RoleEnum::PTSP->isStaff());
        $this->assertTrue(RoleEnum::SECURITY_GUARD->isStaff());
    }

    public function test_principal_does_not_receive_user_creation_permission_by_default(): void
    {
        $principalPerms = PermissionEnum::defaultForRole(RoleEnum::PRINCIPAL);

        $this->assertContains(PermissionEnum::USERS_VIEW, $principalPerms);
        $this->assertNotContains(PermissionEnum::USERS_CREATE, $principalPerms);
    }

    public function test_service_workflow_enforces_valid_transitions_and_mandatory_reasons(): void
    {
        $this->assertTrue(ServiceWorkflowMap::canTransition(
            ServiceWorkflowMap::STATUS_SUBMITTED,
            ServiceWorkflowMap::STATUS_IN_REVIEW,
        ));
        $this->assertFalse(ServiceWorkflowMap::canTransition(
            ServiceWorkflowMap::STATUS_COMPLETED,
            ServiceWorkflowMap::STATUS_IN_REVIEW,
        ));

        $this->expectException(InvalidArgumentException::class);
        ServiceWorkflowMap::assertCanTransition(
            ServiceWorkflowMap::STATUS_IN_REVIEW,
            ServiceWorkflowMap::STATUS_REJECTED,
            '   ',
        );
    }

    public function test_data_classification_keeps_bk_strictly_restricted(): void
    {
        $matrix = DataClassificationMap::matrix();

        $this->assertSame([], $matrix['bk_disiplin']['public_fields']);
        $this->assertSame([], $matrix['bk_disiplin']['private_fields']);
        $this->assertContains('incident_chronology', $matrix['bk_disiplin']['restricted_fields']);
        $this->assertContains('kronologi', DataClassificationMap::sensitiveKeys());
        $this->assertContains('verification_secret', DataClassificationMap::sensitiveKeys());
    }
}

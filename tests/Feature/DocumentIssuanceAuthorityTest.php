<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Application\Services\GraduationService;
use App\Modules\Academic\Application\Services\StudentMutationService;
use App\Modules\Academic\Infrastructure\Models\GraduationBatch;
use App\Modules\Academic\Infrastructure\Models\GraduationRecord;
use App\Modules\Academic\Infrastructure\Models\StudentMutation;
use App\Modules\Administration\Application\Services\LegalizationService;
use App\Modules\Administration\Application\Services\PtspWorkflowService;
use App\Modules\Administration\Infrastructure\Models\LegalizationRequest;
use App\Modules\Administration\Infrastructure\Models\PtspRequest;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentIssuanceAuthorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifiers_cannot_issue_documents_by_calling_services_directly(): void
    {
        $school = School::query()->create(['name' => 'Sekolah Otorisasi', 'npsn' => '99112233', 'level' => 'SMP']);
        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();
        foreach ([RoleEnum::OPERATOR, RoleEnum::PTSP] as $role) {
            $actor = User::query()->create(['school_id' => $school->id, 'role_id' => $roles[$role->value]->id,
                'name' => $role->label(), 'email' => $role->value.'@authority.test', 'password' => 'Password123', 'is_active' => true]);
            $attributes = ['school_id' => $school->id];
            $actions = [
                fn () => app(PtspWorkflowService::class)->issueResult($actor, new PtspRequest($attributes), ['delivery_mode' => 'digital']),
                fn () => app(LegalizationService::class)->approveAndStamp($actor, new LegalizationRequest($attributes)),
                fn () => app(StudentMutationService::class)->decide($actor, new StudentMutation($attributes), 'approve'),
                fn () => app(StudentMutationService::class)->revokeApproval($actor, new StudentMutation($attributes), 'Pembatalan'),
                fn () => app(GraduationService::class)->publishBatch($actor, new GraduationBatch($attributes), 'Pejabat', 'Kepala'),
                fn () => app(GraduationService::class)->reissueSkl($actor, new GraduationRecord($attributes), 'Koreksi'),
            ];
            foreach ($actions as $action) {
                try {
                    $action();
                    $this->fail('Verifier must not issue or revoke official documents.');
                } catch (HttpException $error) {
                    $this->assertSame(403, $error->getStatusCode());
                }
            }
        }
    }

    public function test_principal_and_signatory_get_issuance_controls_without_catalog_management(): void
    {
        $school = School::query()->create(['name' => 'Sekolah Penandatangan', 'npsn' => '99112234', 'level' => 'SMP']);
        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();
        foreach ([RoleEnum::PRINCIPAL, RoleEnum::SIGNATORY] as $role) {
            $actor = User::query()->create(['school_id' => $school->id, 'role_id' => $roles[$role->value]->id,
                'name' => $role->label(), 'email' => $role->value.'@issuer.test', 'password' => 'Password123', 'is_active' => true]);
            $this->actingAs($actor)->get('/administrasi/ptsp')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('canPublish', true)->where('canProcess', false));
            $this->get('/administrasi/legalisir')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('canPublish', true)->where('canProcess', false));
            $this->get('/akademik/kelulusan')->assertOk()->assertInertia(fn (Assert $page) => $page->where('canPublish', true));
            $this->get('/akademik/mutasi')->assertOk()->assertInertia(fn (Assert $page) => $page->where('canPublish', true));
            $this->post('/administrasi/ptsp/katalog', [])->assertForbidden();
            if ($role === RoleEnum::SIGNATORY) {
                $this->post('/akademik/kelulusan/batch', [])->assertForbidden();
            }
        }
    }
}

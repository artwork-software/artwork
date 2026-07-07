<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ControllerTest extends TestCase
{
    private function scope(ExternalAccess $access): ExternalAccessScope
    {
        return ExternalAccessScope::create([
            'external_access_id' => $access->id,
            'project_id' => Project::factory()->create()->id,
            'project_tab_id' => ProjectTab::factory()->create()->id,
            'access_type' => ExternalAccessType::READ,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDays(10),
        ]);
    }

    #[Test]
    public function index_requires_crm_view_permission(): void
    {
        $this->actingAsUserWith([]); // seed, no permission
        $this->actingAs(User::factory()->create());

        $this->get(route('crm.external-access.index'))->assertForbidden();
    }

    #[Test]
    public function index_renders_with_crm_view(): void
    {
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        ExternalAccess::factory()->count(2)->create();

        $this->get(route('crm.external-access.index', ['status' => 'active']))->assertOk();
    }

    #[Test]
    public function show_forbidden_without_crm_view(): void
    {
        $this->actingAsUserWith([]);
        $this->actingAs(User::factory()->create());
        $access = ExternalAccess::factory()->create();

        $this->get(route('crm.external-access.show', $access->id))->assertForbidden();
    }

    #[Test]
    public function extend_crm_access_works_for_inviter(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);

        $this->patch(route('crm.external-access.extend-crm-access', $access->id), [
            'expires_at' => now()->addYear()->toDateTimeString(),
        ])->assertStatus(302);

        $this->assertTrue($access->fresh()->crm_access_expires_at->isFuture());
    }

    #[Test]
    public function extend_crm_access_works_for_crm_manager(): void
    {
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW, PermissionEnum::CRM_MANAGER]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => User::factory()->create()->id]);

        $this->patch(route('crm.external-access.extend-crm-access', $access->id), [
            'expires_at' => now()->addYear()->toDateTimeString(),
        ])->assertStatus(302);
    }

    #[Test]
    public function extend_crm_access_rejects_non_inviter_non_manager(): void
    {
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $this->actingAs(User::factory()->create());
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => User::factory()->create()->id]);

        $this->patch(route('crm.external-access.extend-crm-access', $access->id), [
            'expires_at' => now()->addYear()->toDateTimeString(),
        ])->assertForbidden();
    }

    #[Test]
    public function relink_rejects_inviter_without_manager_role(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);

        $this->post(route('crm.external-access.relink', $access->id), [
            'new_crm_contact_id' => ExternalAccess::factory()->create()->crm_contact_id,
        ])->assertForbidden();
    }

    #[Test]
    public function update_scope_rejects_when_scope_does_not_belong_to_access(): void
    {
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW, PermissionEnum::CRM_MANAGER]);
        $accessA = ExternalAccess::factory()->create();
        $accessB = ExternalAccess::factory()->create();
        $scopeB = $this->scope($accessB);

        $this->patch(route('crm.external-access.scope.update', [$accessA->id, $scopeB->id]), [
            'access_type' => 'write',
        ])->assertNotFound();
    }
}

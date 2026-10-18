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
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Nachträgliches Ändern einer Tab-Freigabe folgt derselben Regel wie die Einladung
 * (ExternalScopeGrantGuard): Tab sichtbar, Schreiben nur mit Projekt-Schreibrecht.
 * Vorher ließ sich eine Lese- per updateScope ohne jede Prüfung zur Schreibfreigabe machen.
 */
final class UpdateScopeGrantRuleTest extends TestCase
{
    private Project $project;

    private function crmManager(?bool $projectWrite): User
    {
        $this->project = Project::factory()->create();
        $user = $this->actingAsUserWith([PermissionEnum::CRM_VIEW, PermissionEnum::CRM_MANAGER]);
        if ($projectWrite !== null) {
            $this->project->users()->attach($user->id, ['can_write' => $projectWrite]);
        }

        return $user;
    }

    private function scope(ExternalAccessType $accessType, ?ProjectTab $tab = null): ExternalAccessScope
    {
        return ExternalAccessScope::create([
            'external_access_id' => ExternalAccess::factory()->create()->id,
            'project_id' => $this->project->id,
            'project_tab_id' => ($tab ?? ProjectTab::factory()->create())->id,
            'access_type' => $accessType,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDays(10),
        ]);
    }

    #[Test]
    public function upgrading_to_write_requires_project_write_rights(): void
    {
        $this->crmManager(false);
        $scope = $this->scope(ExternalAccessType::READ);

        $this->patch(route('crm.external-access.scope.update', [$scope->external_access_id, $scope->id]), [
            'access_type' => ExternalAccessType::WRITE->value,
        ])->assertSessionHasErrors('access_type');

        $this->assertSame(ExternalAccessType::READ, $scope->fresh()->access_type);
    }

    #[Test]
    public function extending_a_write_scope_requires_project_write_rights(): void
    {
        $this->crmManager(null);
        $scope = $this->scope(ExternalAccessType::WRITE);

        $this->patch(route('crm.external-access.scope.update', [$scope->external_access_id, $scope->id]), [
            'valid_to' => now()->addMonths(2)->toDateString(),
        ])->assertSessionHasErrors('access_type');

        $this->assertSame(now()->addDays(10)->toDateString(), $scope->fresh()->valid_to->toDateString());
    }

    #[Test]
    public function project_writer_may_upgrade_to_write(): void
    {
        $this->crmManager(true);
        $scope = $this->scope(ExternalAccessType::READ);

        $this->patch(route('crm.external-access.scope.update', [$scope->external_access_id, $scope->id]), [
            'access_type' => ExternalAccessType::WRITE->value,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(ExternalAccessType::WRITE, $scope->fresh()->access_type);
    }

    #[Test]
    public function downgrading_to_read_is_always_allowed(): void
    {
        $this->crmManager(null);
        $scope = $this->scope(ExternalAccessType::WRITE, ProjectTab::factory()->create(['visible_for_all' => false]));

        $this->patch(route('crm.external-access.scope.update', [$scope->external_access_id, $scope->id]), [
            'access_type' => ExternalAccessType::READ->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(ExternalAccessType::READ, $scope->fresh()->access_type);
    }

    #[Test]
    public function scopes_on_tabs_hidden_from_the_manager_cannot_be_extended(): void
    {
        $this->crmManager(true);
        $scope = $this->scope(ExternalAccessType::READ, ProjectTab::factory()->create(['visible_for_all' => false]));

        $this->patch(route('crm.external-access.scope.update', [$scope->external_access_id, $scope->id]), [
            'valid_to' => now()->addMonths(2)->toDateString(),
        ])->assertSessionHasErrors('access_type');
    }
}

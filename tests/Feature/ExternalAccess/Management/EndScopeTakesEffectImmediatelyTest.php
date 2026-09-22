<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * „Jetzt beenden“ auf einen einzelnen Tab-Scope sperrt genau diesen Tab sofort — auch in einer
 * bereits laufenden Gastsitzung — und lässt andere Scopes desselben Zugangs unberührt.
 */
final class EndScopeTakesEffectImmediatelyTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::shouldUse('web');

        parent::tearDown();
    }

    #[Test]
    public function ending_a_scope_blocks_that_tab_for_the_running_guest_session_but_not_other_tabs(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $external = ExternalAccess::factory()->active()->create(['invited_by_user_id' => $inviter->id]);
        $project = Project::factory()->create();
        $tabA = ProjectTab::factory()->create();
        $tabB = ProjectTab::factory()->create();
        $scopeA = ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tabA->id,
            'access_type' => ExternalAccessType::WRITE->value,
        ]);
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tabB->id,
            'access_type' => ExternalAccessType::READ->value,
        ]);

        // Gast kann beide Tabs öffnen.
        $this->actingAs($external, 'external');
        $this->get(route('external.project.tab.show', [$project->id, $tabA->id]))->assertOk();
        $this->get(route('external.project.tab.show', [$project->id, $tabB->id]))->assertOk();

        // Einladende Person beendet Scope A.
        Auth::shouldUse('web');
        $this->actingAs($inviter, 'web');
        $this->post(route('crm.external-access.scope.end', [$external->id, $scopeA->id]))->assertRedirect();
        $this->assertTrue($scopeA->fresh()->valid_to->lte(now()));

        // Gast: Tab A gesperrt (lesen UND schreiben), Tab B weiterhin offen, Zugang selbst aktiv.
        $this->actingAs($external, 'external');
        $this->get(route('external.project.tab.show', [$project->id, $tabA->id]))->assertForbidden();
        $this->post(route('external.project.tab.submit', [$project->id, $tabA->id]))->assertForbidden();
        $this->get(route('external.project.tab.show', [$project->id, $tabB->id]))->assertOk();
        $this->get(route('external.dashboard'))->assertOk();
    }

    #[Test]
    public function scope_of_another_access_cannot_be_ended_through_a_foreign_access_url(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $own = ExternalAccess::factory()->active()->create(['invited_by_user_id' => $inviter->id]);
        $foreign = ExternalAccess::factory()->active()->create(['invited_by_user_id' => $inviter->id]);
        $foreignScope = ExternalAccessScope::factory()->create(['external_access_id' => $foreign->id]);

        $response = $this->post(route('crm.external-access.scope.end', [$own->id, $foreignScope->id]));

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertTrue($foreignScope->fresh()->valid_to->gt(now()), 'Fremder Scope darf nicht beendet werden');
    }
}

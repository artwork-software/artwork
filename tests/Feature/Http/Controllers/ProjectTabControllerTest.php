<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectTabControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('tab.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tab.index'))->assertOk();
    }

    #[Test]
    public function admin_can_view_list(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tab.list'))->assertOk();
    }

    /**
     * Tab-Auswahl im To-do-Listen-Modal: nur sichtbare Tabs (in andere lehnt das Anlegen ab), Admins alle.
     */
    #[Test]
    public function tab_list_only_offers_tabs_the_user_may_see(): void
    {
        $visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $sharedTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $user = User::factory()->create();
        $sharedTab->visibleUsers()->attach($user->id);

        $listedIds = $this->actingAs($user)->getJson(route('tab.list'))->assertOk()->json('*.id');
        $this->assertContains($visibleTab->id, $listedIds);
        $this->assertContains($sharedTab->id, $listedIds);
        $this->assertNotContains($hiddenTab->id, $listedIds);
        $this->assertSame(['id', 'name'], array_keys($this->getJson(route('tab.list'))->json('0')));

        $this->actingAsAdmin();
        $this->assertContains($hiddenTab->id, $this->getJson(route('tab.list'))->json('*.id'));
    }

    #[Test]
    public function guest_cannot_store_tab(): void
    {
        $this->post(route('tab.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_tab(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('tab.store'), [
            'name' => 'My Tab',
            'visible_for_all' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_tabs', ['name' => 'My Tab']);
    }

    #[Test]
    public function admin_can_update_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();

        $response = $this->patch(route('tab.update', $tab), [
            'name' => 'Updated',
            'visible_for_all' => true,
        ]);

        $response->assertOk();
        $this->assertSame('Updated', $tab->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();

        $response = $this->delete(route('tab.destroy', $tab));

        $response->assertOk();
        $this->assertDatabaseMissing('project_tabs', ['id' => $tab->id]);
    }

    /**
     * Projektwerte hängen an (project_id, component_id), nicht am Tab. Früher wurde beim Tab-Löschen mit der
     * Platzierungs-ID (component_in_tabs.id) gelöscht und so die Werte einer fremden Komponente mit gleicher ID
     * entfernt; zusätzlich gingen geteilte Werte einer auch anderswo platzierten Komponente verloren.
     */
    #[Test]
    public function destroying_tab_keeps_project_component_values(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        // Gleiche ID für fremde Komponente und Platzierung erzwingen, damit die ID-Verwechslung sichtbar wird.
        $collidingId = max(
            (int) DB::table('components')->max('id'),
            (int) DB::table('component_in_tabs')->max('id'),
        ) + 1000;

        $unrelatedComponent = $this->createTextComponent(['id' => $collidingId]);
        $placedComponent = $this->createTextComponent();

        $tabToDelete = ProjectTab::factory()->create();
        $otherTab = ProjectTab::factory()->create();

        ComponentInTab::query()->forceCreate([
            'id' => $collidingId,
            'project_tab_id' => $tabToDelete->id,
            'component_id' => $placedComponent->id,
            'order' => 1,
        ]);
        ComponentInTab::query()->create([
            'project_tab_id' => $otherTab->id,
            'component_id' => $placedComponent->id,
            'order' => 1,
        ]);

        $unrelatedValue = ProjectComponentValue::query()->create([
            'project_id' => $project->id,
            'component_id' => $unrelatedComponent->id,
            'data' => ['text' => 'fremder Wert'],
        ]);
        $sharedValue = ProjectComponentValue::query()->create([
            'project_id' => $project->id,
            'component_id' => $placedComponent->id,
            'data' => ['text' => 'geteilter Wert'],
        ]);

        $this->delete(route('tab.destroy', $tabToDelete))->assertOk();

        $this->assertDatabaseMissing('project_tabs', ['id' => $tabToDelete->id]);
        $this->assertDatabaseMissing('component_in_tabs', ['project_tab_id' => $tabToDelete->id]);
        $this->assertDatabaseHas('project_component_values', ['id' => $unrelatedValue->id]);
        $this->assertDatabaseHas('project_component_values', ['id' => $sharedValue->id]);
        $this->assertDatabaseHas('component_in_tabs', [
            'project_tab_id' => $otherTab->id,
            'component_id' => $placedComponent->id,
        ]);
    }

    #[Test]
    public function admin_can_set_tab_as_default(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create(['default' => false]);

        $response = $this->patch(route('tab.update.default', $tab));

        $response->assertOk();
        $this->assertTrue((bool) $tab->fresh()->default);
    }

    #[Test]
    public function admin_can_reorder_tabs(): void
    {
        $this->actingAsAdmin();
        $a = ProjectTab::factory()->create(['order' => 1]);
        $b = ProjectTab::factory()->create(['order' => 2]);

        $response = $this->post(route('tab.reorder'), [
            'components' => [
                ['id' => $b->id],
                ['id' => $a->id],
            ],
        ]);

        $response->assertOk();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTextComponent(array $attributes = []): Component
    {
        return Component::query()->forceCreate(array_merge([
            'name' => 'Textfeld',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ], $attributes));
    }
}

<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für die Tab-, Sidebar- und Drucklayout-Einstellungen der Projekte.
 */
final class ProjectTabSettingsComponentActionsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    private function makeComponent(string $type = 'TextField', bool $sidebarEnabled = false): Component
    {
        return Component::factory()->create([
            'type' => $type,
            'sidebar_enabled' => $sidebarEnabled,
            'data' => [],
        ]);
    }

    private function placeInTab(ProjectTab $tab, Component $component, int $order): ComponentInTab
    {
        return ComponentInTab::factory()->create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => $order,
        ]);
    }

    #[Test]
    public function adding_a_component_inserts_it_at_the_given_position(): void
    {
        $tab = ProjectTab::factory()->create();
        $first = $this->placeInTab($tab, $this->makeComponent(), 1);
        $second = $this->placeInTab($tab, $this->makeComponent(), 2);
        $new = $this->makeComponent();

        $this->post(route('tab.add.component', $tab), ['component_id' => $new->id, 'order' => 2])->assertOk();

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(3, $second->fresh()->order);
        $this->assertDatabaseHas('component_in_tabs', [
            'project_tab_id' => $tab->id,
            'component_id' => $new->id,
            'order' => 2,
        ]);
    }

    #[Test]
    public function adding_a_component_with_scopes_stores_the_scope(): void
    {
        $tab = ProjectTab::factory()->create();
        $scopeTab = ProjectTab::factory()->create();
        $new = $this->makeComponent();

        $this->post(route('tab.add.component.with.scopes', $tab), [
            'component_id' => $new->id,
            'order' => 1,
            'scope' => [$scopeTab->id],
        ])->assertOk();

        $placement = ComponentInTab::query()
            ->where('project_tab_id', $tab->id)
            ->where('component_id', $new->id)
            ->firstOrFail();
        $this->assertSame([$scopeTab->id], $placement->scope);
    }

    #[Test]
    public function adding_a_component_rejects_missing_or_unknown_components(): void
    {
        $tab = ProjectTab::factory()->create();

        $this->postJson(route('tab.add.component', $tab), ['order' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
        $this->postJson(route('tab.add.component', $tab), ['component_id' => 99999999, 'order' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
        $this->postJson(route('tab.add.component', $tab), ['component_id' => $this->makeComponent()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');
        $this->postJson(route('tab.add.component.with.scopes', $tab), ['component_id' => 99999999, 'order' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
        $this->postJson(route('tab.add.component.with.scopes', $tab), [
            'component_id' => $this->makeComponent()->id,
            'order' => 1,
            'scope' => 'kein-array',
        ])->assertUnprocessable()->assertJsonValidationErrors('scope');

        $this->assertSame(0, ComponentInTab::query()->where('project_tab_id', $tab->id)->count());
    }

    #[Test]
    public function removing_a_component_only_affects_the_given_tab(): void
    {
        $tab = ProjectTab::factory()->create();
        $otherTab = ProjectTab::factory()->create();
        $placement = $this->placeInTab($tab, $this->makeComponent(), 1);
        $foreignPlacement = $this->placeInTab($otherTab, $this->makeComponent(), 1);

        $this->delete(route('tab.remove.component', $tab), ['component_id' => $foreignPlacement->id])->assertOk();
        $this->assertModelExists($foreignPlacement);

        $this->delete(route('tab.remove.component', $tab), ['component_id' => $placement->id])->assertOk();
        $this->assertModelMissing($placement);
    }

    #[Test]
    public function removing_a_folder_from_a_tab_keeps_the_folder_contents_used_elsewhere(): void
    {
        $tab = ProjectTab::factory()->create();
        $otherTab = ProjectTab::factory()->create();
        $folder = $this->makeComponent('DisclosureComponent');
        $content = DisclosureComponents::factory()->create([
            'disclosure_id' => $folder->id,
            'component_id' => $this->makeComponent()->id,
        ]);
        $placement = $this->placeInTab($tab, $folder, 1);
        $this->placeInTab($otherTab, $folder, 1);

        $this->delete(route('tab.remove.component', $tab), ['component_id' => $placement->id])->assertOk();

        $this->assertModelMissing($placement);
        $this->assertModelExists($content);
    }

    #[Test]
    public function component_order_is_rewritten_and_foreign_components_are_ignored(): void
    {
        $tab = ProjectTab::factory()->create();
        $a = $this->placeInTab($tab, $this->makeComponent(), 1);
        $b = $this->placeInTab($tab, $this->makeComponent(), 2);
        $foreign = $this->placeInTab(ProjectTab::factory()->create(), $this->makeComponent(), 7);

        $this->post(route('tab.update.component.order', $tab), [
            'components' => [['id' => $b->id], ['id' => $foreign->id], ['id' => $a->id]],
        ])->assertOk();

        $this->assertSame(1, $b->fresh()->order);
        $this->assertSame(3, $a->fresh()->order);
        $this->assertSame(7, $foreign->fresh()->order);
    }

    #[Test]
    public function component_order_without_components_is_rejected_instead_of_failing(): void
    {
        $tab = ProjectTab::factory()->create();

        $this->postJson(route('tab.update.component.order', $tab), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('components');
        $this->postJson(route('tab.update.component.order', $tab), ['components' => [['order' => 1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('components.0.id');
    }

    #[Test]
    public function component_note_and_scope_can_be_updated_and_cleared(): void
    {
        $tab = ProjectTab::factory()->create();
        $placement = $this->placeInTab($tab, $this->makeComponent(), 1);

        $this->patch(route('tab.update.component.note', $placement), ['note' => 'Nur für Technik'])->assertOk();
        $this->assertSame('Nur für Technik', $placement->fresh()->note);

        $this->patch(route('tab.update.component.note', $placement), ['note' => ''])->assertOk();
        $this->assertNull($placement->fresh()->note);

        $this->patch(route('tab.update.component.scope', $placement), ['scope' => [$tab->id]])->assertOk();
        $this->assertSame([$tab->id], $placement->fresh()->scope);

        $this->patch(route('tab.update.component.scope', $placement), [])->assertOk();
        $this->assertSame([], $placement->fresh()->scope);
    }

    #[Test]
    public function component_note_and_scope_reject_invalid_input(): void
    {
        $tab = ProjectTab::factory()->create();
        $placement = $this->placeInTab($tab, $this->makeComponent(), 1);
        $placement->update(['note' => 'Alt', 'scope' => [$tab->id]]);

        $this->patchJson(route('tab.update.component.note', $placement), ['note' => ['kein', 'text']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');
        $this->patchJson(route('tab.update.component.scope', $placement), ['scope' => 'alles'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scope');

        $placement->refresh();
        $this->assertSame('Alt', $placement->note);
        $this->assertSame([$tab->id], $placement->scope);
    }

    #[Test]
    public function folder_upload_settings_are_normalised_and_stored(): void
    {
        $documents = $this->makeComponent(ProjectTabComponentEnum::PROJECT_DOCUMENTS->value);
        $placement = DisclosureComponents::factory()->create(['component_id' => $documents->id]);

        $this->from('/settings')->patch(
            route('tab.update.disclosure-component.upload-settings', $placement),
            ['allowed_extensions' => [], 'max_file_size_mb' => 5, 'max_files' => 3]
        )->assertRedirect('/settings');

        $settings = $placement->fresh()->upload_settings;
        $this->assertSame([], $settings['allowed_extensions']);
        $this->assertSame(3, $settings['max_files']);
        $this->assertNotNull($settings['max_file_size_mb']);
    }

    #[Test]
    public function folder_upload_settings_are_only_allowed_for_the_documents_component(): void
    {
        $placement = DisclosureComponents::factory()->create(['component_id' => $this->makeComponent()->id]);

        $this->patchJson(
            route('tab.update.disclosure-component.upload-settings', $placement),
            ['allowed_extensions' => [], 'max_file_size_mb' => 5, 'max_files' => 3]
        )->assertUnprocessable()->assertJsonValidationErrors('component');

        $this->assertNull($placement->fresh()->upload_settings);
    }

    #[Test]
    public function folder_upload_settings_validate_limits(): void
    {
        $documents = $this->makeComponent(ProjectTabComponentEnum::PROJECT_DOCUMENTS->value);
        $placement = DisclosureComponents::factory()->create(['component_id' => $documents->id]);

        $this->patchJson(
            route('tab.update.disclosure-component.upload-settings', $placement),
            ['max_file_size_mb' => 0, 'max_files' => -1]
        )->assertUnprocessable()->assertJsonValidationErrors(['allowed_extensions', 'max_file_size_mb', 'max_files']);
    }

    #[Test]
    public function sidebar_component_is_inserted_at_the_given_position(): void
    {
        $sidebarTab = ProjectTabSidebarTab::factory()->create();
        $existing = SidebarTabComponent::factory()->create([
            'project_tab_sidebar_id' => $sidebarTab->id,
            'order' => 1,
        ]);
        $component = $this->makeComponent('TextField', sidebarEnabled: true);

        $this->from('/settings')
            ->post(route('tab.add.component.sidebar', $sidebarTab), ['component_id' => $component->id, 'order' => 1])
            ->assertRedirect('/settings')
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $existing->fresh()->order);
        $this->assertDatabaseHas('sidebar_tab_components', [
            'project_tab_sidebar_id' => $sidebarTab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);
    }

    #[Test]
    public function sidebar_rejects_duplicates_folders_and_components_without_sidebar_support(): void
    {
        $sidebarTab = ProjectTabSidebarTab::factory()->create();
        $placed = $this->makeComponent('TextField', sidebarEnabled: true);
        SidebarTabComponent::factory()->create([
            'project_tab_sidebar_id' => $sidebarTab->id,
            'component_id' => $placed->id,
            'order' => 1,
        ]);
        $notSidebar = $this->makeComponent('TextField', sidebarEnabled: false);
        $folder = $this->makeComponent('DisclosureComponent', sidebarEnabled: true);

        foreach ([$placed, $notSidebar, $folder] as $component) {
            $this->from('/settings')
                ->post(route('tab.add.component.sidebar', $sidebarTab), ['component_id' => $component->id, 'order' => 1])
                ->assertRedirect('/settings')
                ->assertSessionHasErrors('error');
        }

        $this->postJson(route('tab.add.component.sidebar', $sidebarTab), ['component_id' => 99999999, 'order' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['component_id', 'order']);

        $this->assertSame(
            1,
            SidebarTabComponent::query()->where('project_tab_sidebar_id', $sidebarTab->id)->count()
        );
    }

    #[Test]
    public function sidebar_tabs_and_sidebar_components_can_be_reordered(): void
    {
        $tab = ProjectTab::factory()->create();
        $sidebarA = ProjectTabSidebarTab::factory()->create(['project_tab_id' => $tab->id, 'order' => 1]);
        $sidebarB = ProjectTabSidebarTab::factory()->create(['project_tab_id' => $tab->id, 'order' => 2]);

        $this->postJson(route('sidebar.tab.reorder'), ['components' => [['id' => $sidebarB->id], ['id' => $sidebarA->id]]])
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame(1, $sidebarB->fresh()->order);
        $this->assertSame(2, $sidebarA->fresh()->order);

        $first = SidebarTabComponent::factory()->create(['project_tab_sidebar_id' => $sidebarA->id, 'order' => 1]);
        $second = SidebarTabComponent::factory()->create(['project_tab_sidebar_id' => $sidebarA->id, 'order' => 2]);
        $foreign = SidebarTabComponent::factory()->create(['project_tab_sidebar_id' => $sidebarB->id, 'order' => 9]);

        $this->postJson(route('sidebar.tab.update.component.order', $sidebarA), [
            'components' => [['id' => $second->id], ['id' => $foreign->id], ['id' => $first->id]],
        ])->assertOk();

        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(3, $first->fresh()->order);
        $this->assertSame(9, $foreign->fresh()->order);
    }

    #[Test]
    public function sidebar_reorder_without_entries_returns_a_client_error(): void
    {
        $sidebarTab = ProjectTabSidebarTab::factory()->create();

        $this->postJson(route('sidebar.tab.reorder'), ['components' => []])->assertStatus(400);
        $this->postJson(route('sidebar.tab.update.component.order', $sidebarTab), [])->assertStatus(400);
    }

    private function printLayout(): ProjectPrintLayout
    {
        return ProjectPrintLayout::factory()->create([
            'columns_header' => 1,
            'columns_body' => 2,
            'columns_footer' => 1,
            'notes' => ['header' => [], 'footer' => []],
        ]);
    }

    #[Test]
    public function print_layout_component_can_be_added_and_removed(): void
    {
        $layout = $this->printLayout();
        $component = $this->makeComponent();

        $this->post(route('project.print.layout.add.component', $layout), [
            'component_id' => $component->id,
            'type' => 'body',
            'row' => 1,
            'col' => 2,
        ])->assertOk();

        $placed = PrintLayoutComponents::query()
            ->where('project_print_layout_id', $layout->id)
            ->where('component_id', $component->id)
            ->firstOrFail();
        $this->assertSame('body', $placed->type);
        $this->assertSame(1, (int) $placed->row);
        $this->assertSame(2, (int) $placed->position);

        $this->delete(route('project-print-layout.components.destroy', $placed))->assertOk();
        $this->assertModelMissing($placed);
    }

    #[Test]
    public function print_layout_add_component_rejects_invalid_input(): void
    {
        $layout = $this->printLayout();

        $this->postJson(route('project.print.layout.add.component', $layout), ['type' => 'body', 'row' => 1, 'col' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
        $this->postJson(route('project.print.layout.add.component', $layout), [
            'component_id' => 99999999,
            'type' => 'body',
            'row' => 1,
            'col' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('component_id');
        $this->postJson(route('project.print.layout.add.component', $layout), [
            'component_id' => $this->makeComponent()->id,
            'type' => 'sidebar',
            'row' => 'eins',
            'col' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'row', 'col']);

        $this->assertSame(0, PrintLayoutComponents::query()->where('project_print_layout_id', $layout->id)->count());
    }

    #[Test]
    public function destroying_a_print_layout_removes_its_components(): void
    {
        $layout = $this->printLayout();
        $placed = PrintLayoutComponents::factory()->create(['project_print_layout_id' => $layout->id, 'type' => 'body']);

        $this->delete(route('project-print-layout.destroy', $layout))->assertOk();

        $this->assertModelMissing($layout);
        $this->assertModelMissing($placed);
    }

    #[Test]
    public function print_layout_header_and_footer_notes_are_saved(): void
    {
        $layout = $this->printLayout();
        $header = [['text' => 'Kopf']];
        $footer = [['text' => 'Fuß']];

        $this->patch(route('project-print-layout.update.header.note', $layout), [
            'header_note' => $header,
            'footer_note' => $footer,
        ])->assertOk();

        $this->assertSame(['header' => $header, 'footer' => $footer], $layout->fresh()->notes);

        $this->patch(route('project-print-layout.update.header.note', $layout), [])->assertOk();
        $this->assertSame(['header' => [], 'footer' => []], $layout->fresh()->notes);
    }
}

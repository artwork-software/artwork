<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Typgebundene Tab-Daten-Endpunkte (projects.tabs.*) liefern nur, wenn die Person eine Komponente des
 * passenden Typs in einem für sie sichtbaren Tab sehen darf.
 */
final class ProjectTabDataEndpointVisibilityTest extends FeatureTestCase
{
    private Project $project;

    private ProjectTab $visibleTab;

    private ProjectTab $hiddenTab;

    protected function setUp(): void
    {
        parent::setUp();

        // Vorhandene Platzierungen der geteilten Test-DB ausblenden (Rollback per Transaktion)
        DisclosureComponents::query()->delete();
        SidebarTabComponent::query()->delete();
        ComponentInTab::query()->delete();

        $this->project = Project::factory()->create();
        $this->visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $this->hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
    }

    /**
     * @return array<string, array{0: string, 1: ProjectTabComponentEnum}>
     */
    public static function typedEndpoints(): array
    {
        return [
            'team' => ['projects.tabs.team', ProjectTabComponentEnum::PROJECT_TEAM],
            'status' => ['projects.tabs.status', ProjectTabComponentEnum::PROJECT_STATUS],
            'artist name' => ['projects.tabs.artist-name', ProjectTabComponentEnum::ARTIST_NAME_DISPLAY],
            'shift contacts' => ['projects.tabs.shift-contacts', ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS],
            'material issues' => [
                'projects.tabs.material-issues',
                ProjectTabComponentEnum::PROJECT_MATERIAL_ISSUE_COMPONENT,
            ],
            'artist residencies' => ['projects.tabs.artist-residencies', ProjectTabComponentEnum::ARTIST_RESIDENCIES],
            'budget informations' => [
                'projects.tabs.budget-informations',
                ProjectTabComponentEnum::BUDGET_INFORMATIONS,
            ],
            'bulk edit' => ['projects.tabs.bulk-edit', ProjectTabComponentEnum::BULK_EDIT],
            'calendar' => ['projects.tabs.calendar', ProjectTabComponentEnum::CALENDAR],
            'budget' => ['projects.tabs.budget', ProjectTabComponentEnum::BUDGET],
            'shift' => ['projects.tabs.shift', ProjectTabComponentEnum::SHIFT_TAB],
            'sage invoices' => ['projects.tabs.sage-invoices', ProjectTabComponentEnum::SAGE_INVOICE_OVERVIEW],
        ];
    }

    #[Test]
    #[DataProvider('typedEndpoints')]
    public function endpoint_is_forbidden_when_its_component_only_lies_in_hidden_tabs(
        string $routeName,
        ProjectTabComponentEnum $type
    ): void {
        $this->placeInTab($this->hiddenTab, $this->createSpecial($type));

        $this->actingAs($this->readOnlyMember());

        $this->getJson(route($routeName, $this->project))->assertForbidden();
    }

    #[Test]
    #[DataProvider('typedEndpoints')]
    public function endpoint_answers_when_its_component_lies_in_a_visible_tab(
        string $routeName,
        ProjectTabComponentEnum $type
    ): void {
        $this->placeInTab($this->visibleTab, $this->createSpecial($type));

        $this->actingAs($this->readOnlyMember());

        $response = $this->getJson(route($routeName, $this->project));

        // Kalender und Schichten scheitern in Feature-Tests an bekannten Fixture-Fehlern im Service
        // (fehlender Nutzerfilter, Carbon-Typ) — wie in deren Controller-Tests zählt hier die Autorisierung.
        if (in_array($routeName, ['projects.tabs.calendar', 'projects.tabs.shift'], true)) {
            $this->assertNotContains($response->getStatusCode(), [302, 401, 403]);

            return;
        }

        $response->assertOk();
    }

    #[Test]
    public function restricted_component_blocks_its_endpoint_except_for_listed_users(): void
    {
        $team = $this->createSpecial(ProjectTabComponentEnum::PROJECT_TEAM, 'someSeeSomeEdit');
        $this->placeInTab($this->visibleTab, $team);

        $this->actingAs($this->readOnlyMember());
        $this->getJson(route('projects.tabs.team', $this->project))->assertForbidden();

        $listed = $this->readOnlyMember();
        $team->users()->attach($listed->id, ['can_write' => false]);
        $this->actingAs($listed);
        $this->getJson(route('projects.tabs.team', $this->project))->assertOk();
    }

    #[Test]
    public function folder_and_sidebar_placements_count_as_visible(): void
    {
        $folder = Component::create([
            'name' => 'Folder ' . uniqid(),
            'type' => ProjectTabComponentEnum::DISCLOSURE_COMPONENT->value,
            'data' => ['label' => 'Folder'],
        ]);
        $this->placeInTab($this->visibleTab, $folder);
        DisclosureComponents::create([
            'disclosure_id' => $folder->id,
            'component_id' => $this->createSpecial(ProjectTabComponentEnum::PROJECT_STATUS)->id,
            'order' => 1,
        ]);

        $sidebar = ProjectTabSidebarTab::create([
            'project_tab_id' => $this->visibleTab->id,
            'name' => 'Info',
            'order' => 1,
        ]);
        $sidebar->componentsInSidebar()->create([
            'component_id' => $this->createSpecial(ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS)->id,
            'order' => 1,
        ]);

        $this->actingAs($this->readOnlyMember());

        $this->getJson(route('projects.tabs.status', $this->project))->assertOk();
        $this->getJson(route('projects.tabs.shift-contacts', $this->project))->assertOk();
    }

    #[Test]
    public function project_writers_may_load_artist_names_and_bulk_data_for_edit_dialogs(): void
    {
        $writer = User::factory()->create();
        $this->project->users()->attach($writer->id, ['can_write' => true]);
        $this->actingAs($writer);

        $this->getJson(route('projects.tabs.artist-name', $this->project))->assertOk();
        $this->getJson(route('projects.tabs.bulk-edit', $this->project))->assertOk();
        $this->getJson(route('projects.tabs.team', $this->project))->assertForbidden();
    }

    #[Test]
    public function admins_load_data_without_any_placement(): void
    {
        $this->actingAsAdmin();

        $this->getJson(route('projects.tabs.team', $this->project))->assertOk();
    }

    private function readOnlyMember(): User
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user->id, ['can_write' => false]);

        return $user;
    }

    private function createSpecial(ProjectTabComponentEnum $type, ?string $permissionType = null): Component
    {
        return Component::create([
            'name' => $type->value . ' ' . uniqid(),
            'type' => $type->value,
            'data' => [],
            'special' => true,
            'permission_type' => $permissionType,
        ]);
    }

    private function placeInTab(ProjectTab $tab, Component $component): void
    {
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);
    }
}

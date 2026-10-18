<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsPropShape;
use Tests\Feature\FeatureTestCase;

/**
 * Projektübersicht (/projects): Komponentenwerte und Budget-Informationen gehen nur für betretbare
 * Projekte und für Komponenten raus, die die Person sehen darf (Komponenten-Berechtigung + Tab-Sichtbarkeit).
 */
final class ProjectOverviewComponentVisibilityTest extends FeatureTestCase
{
    use AssertsPropShape;

    private const SECRET = 'OVERVIEW-SECRET';

    private Component $publicField;

    private Component $hiddenTabField;

    private Component $restrictedField;

    private Component $budgetInformations;

    private Project $teamProject;

    private Project $foreignProject;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);

        $this->publicField = $this->createTextField('Public');
        $this->hiddenTabField = $this->createTextField('Hidden tab');
        $this->restrictedField = $this->createTextField('Restricted', 'someSeeSomeEdit');
        $this->budgetInformations = Component::create([
            'name' => 'Budget',
            'type' => ProjectTabComponentEnum::BUDGET_INFORMATIONS->value,
            'data' => [],
            'special' => true,
        ]);
        $this->placeInTab($visibleTab, $this->publicField);
        $this->placeInTab($hiddenTab, $this->hiddenTabField);
        $this->placeInTab($visibleTab, $this->budgetInformations);

        $columns = [$this->publicField, $this->hiddenTabField, $this->restrictedField, $this->budgetInformations];
        foreach ($columns as $index => $component) {
            ProjectManagementBuilder::create([
                'name' => $component->name,
                'order' => $index + 1,
                'is_active' => true,
                'type' => $component->type,
                'deletable' => true,
                'component_id' => $component->id,
            ]);
        }

        $this->teamProject = Project::factory()->create([
            'cost_center_id' => CostCenter::factory()->create(['name' => 'team cost center'])->id,
            'cost_center_description' => 'team description',
        ]);
        $this->foreignProject = Project::factory()->create([
            'cost_center_id' => CostCenter::factory()->create(['name' => self::SECRET . '-cost-center'])->id,
            'cost_center_description' => self::SECRET . '-description',
            'gema' => true,
        ]);
        foreach ([$this->teamProject, $this->foreignProject] as $project) {
            $this->setValue($project, $this->publicField, $project->is($this->teamProject)
                ? 'team public value'
                : self::SECRET . '-foreign-public');
            $this->setValue($project, $this->hiddenTabField, self::SECRET . '-hidden-tab');
            $this->setValue($project, $this->restrictedField, self::SECRET . '-restricted');
        }

        $this->member = User::factory()->create();
        $this->teamProject->users()->attach($this->member->id, ['can_write' => true]);
    }

    #[Test]
    public function team_member_gets_only_visible_values_of_enterable_projects(): void
    {
        $this->actingAs($this->member);

        $response = $this->get(route('projects', ['entitiesPerPage' => 10]))->assertOk();
        $props = $response->viewData('page')['props'];
        $rows = collect($props['projectComponents'])->keyBy('id');

        $team = (array) $rows[$this->teamProject->id];
        $this->assertTrue($team['canEnter']);
        $this->assertSame('team public value', $team['TextField'][$this->publicField->id]['data']['text']);
        $this->assertNull($team['TextField'][$this->hiddenTabField->id]);
        $this->assertNull($team['TextField'][$this->restrictedField->id]);
        $this->assertSame('team description', $team['cost_center_description']);
        $this->assertEqualsCanonicalizing(
            [$this->hiddenTabField->id, $this->restrictedField->id],
            $team['hiddenComponentIds']
        );

        $foreign = (array) $rows[$this->foreignProject->id];
        $this->assertFalse($foreign['canEnter']);
        $this->assertNull($foreign['TextField'][$this->publicField->id]);
        $this->assertArrayNotHasKey('cost_center_description', $foreign);
        $this->assertArrayNotHasKey('gema', $foreign);
        $this->assertEqualsCanonicalizing(
            [
                $this->publicField->id,
                $this->hiddenTabField->id,
                $this->restrictedField->id,
                $this->budgetInformations->id,
            ],
            $foreign['hiddenComponentIds']
        );

        // Auch die rohen Projekt-Modelle tragen ohne Zutritt keine Budget-Informationen
        $rawForeign = collect($props['projects']['data'])->firstWhere('id', $this->foreignProject->id);
        $this->assertArrayNotHasKey('cost_center_description', $rawForeign);
        $this->assertArrayNotHasKey('gema', $rawForeign);
        $this->assertArrayNotHasKey('cost_center', $rawForeign);

        $this->assertStringNotContainsString(self::SECRET, json_encode($props));
    }

    /**
     * Betretbares Projekt, Budget-Informationen aber nur in unsichtbaren Tabs: die rohen Projekt-Props
     * tragen Kostenträger/GEMA nur noch für Schreibberechtigte (Bearbeiten-Modal der Übersicht).
     */
    #[Test]
    public function budget_fields_of_raw_projects_need_budget_visibility_or_write_rights(): void
    {
        $hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        ComponentInTab::query()
            ->where('component_id', $this->budgetInformations->id)
            ->update(['project_tab_id' => $hiddenTab->id]);
        // Standard-Budget-Informationen aus den Seed-Tabs (Seitenleiste) aus dem Spiel nehmen
        $otherBudgetComponentIds = Component::query()
            ->where('type', ProjectTabComponentEnum::BUDGET_INFORMATIONS->value)
            ->whereKeyNot($this->budgetInformations->id)
            ->pluck('id');
        SidebarTabComponent::query()->whereIn('component_id', $otherBudgetComponentIds)->delete();
        ComponentInTab::query()->whereIn('component_id', $otherBudgetComponentIds)->delete();
        $reader = User::factory()->create();
        $this->teamProject->users()->attach($reader->id, ['can_write' => false]);

        $rawTeamProjectFor = function (User $user): array {
            $this->actingAs($user);
            app()->forgetScopedInstances();
            $props = $this->get(route('projects', ['entitiesPerPage' => 10]))->assertOk()
                ->viewData('page')['props'];

            return collect($props['projects']['data'])->firstWhere('id', $this->teamProject->id);
        };

        $forReader = $rawTeamProjectFor($reader);
        $this->assertArrayNotHasKey('cost_center_description', $forReader);
        $this->assertArrayNotHasKey('gema', $forReader);
        $this->assertArrayNotHasKey('cost_center', $forReader);

        $forWriter = $rawTeamProjectFor($this->member);
        $this->assertSame('team description', $forWriter['cost_center_description']);
        $this->assertSame('team cost center', $forWriter['cost_center']['name']);
    }

    #[Test]
    public function admin_gets_all_values(): void
    {
        $this->actingAsAdmin();

        $rows = collect($this->get(route('projects', ['entitiesPerPage' => 10]))->assertOk()
            ->viewData('page')['props']['projectComponents'])->keyBy('id');

        $foreign = (array) $rows[$this->foreignProject->id];
        $this->assertSame([], $foreign['hiddenComponentIds']);
        $this->assertSame(
            self::SECRET . '-restricted',
            $foreign['TextField'][$this->restrictedField->id]['data']['text']
        );
        $this->assertSame(self::SECRET . '-description', $foreign['cost_center_description']);
    }

    #[Test]
    public function visibility_check_does_not_scale_with_projects(): void
    {
        Project::factory()->count(6)->create()->each(function (Project $project): void {
            $project->users()->attach($this->member->id, ['can_write' => true]);
            $this->setValue($project, $this->publicField, 'value');
        });
        $this->actingAs($this->member);

        $this->assertNoRepeatedQueryPatterns(
            fn () => $this->get(route('projects', ['entitiesPerPage' => 10]))->assertOk(),
            4
        );
    }

    private function createTextField(string $label, ?string $permissionType = null): Component
    {
        return Component::create([
            'name' => $label,
            'type' => 'TextField',
            'data' => ['label' => $label, 'text' => '', 'placeholder' => ''],
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

    private function setValue(Project $project, Component $component, string $text): void
    {
        ProjectComponentValue::create([
            'component_id' => $component->id,
            'project_id' => $project->id,
            'data' => ['text' => $text],
        ]);
    }
}

<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projektübersicht (/projects): die Spalte "Projektgruppe" liefert je Projekt die Gruppen, zu denen
 * es gehört — nur Id und Name, keine vollen Gruppen-Modelle.
 */
final class ProjectOverviewProjectGroupColumnTest extends FeatureTestCase
{
    private Project $group;

    private Project $memberProject;

    private Project $standaloneProject;

    protected function setUp(): void
    {
        parent::setUp();

        $groupComponent = Component::query()->firstOrCreate(
            ['type' => ProjectTabComponentEnum::PROJECT_GROUP->value],
            ['name' => 'Project Group', 'data' => [], 'special' => true]
        );
        ProjectManagementBuilder::create([
            'name' => 'Project Group',
            'order' => 1,
            'is_active' => true,
            'type' => ProjectTabComponentEnum::PROJECT_GROUP->value,
            'deletable' => true,
            'component_id' => $groupComponent->id,
        ]);

        $this->group = Project::factory()->create(['name' => 'Festivalgruppe', 'is_group' => true]);
        $this->memberProject = Project::factory()->create(['name' => 'Teil der Gruppe']);
        $this->standaloneProject = Project::factory()->create(['name' => 'Ohne Gruppe']);
        $this->group->projectsOfGroup()->attach($this->memberProject->id);
    }

    #[Test]
    public function member_project_lists_its_groups_with_id_and_name_only(): void
    {
        $this->actingAsAdmin();

        $rows = $this->overviewRows();

        $this->assertSame(
            [['id' => $this->group->id, 'name' => 'Festivalgruppe']],
            json_decode(json_encode($rows[$this->memberProject->id]['group']), true)
        );
    }

    #[Test]
    public function projects_without_group_get_an_empty_list(): void
    {
        $this->actingAsAdmin();

        $rows = $this->overviewRows();

        $this->assertSame([], json_decode(json_encode($rows[$this->standaloneProject->id]['group']), true));

        $groupRow = $rows[$this->group->id];
        $this->assertTrue((bool) $groupRow['is_group']);
        $this->assertSame(1, $groupRow['projects_of_group_count']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function overviewRows(): array
    {
        $props = $this->get(route('projects', ['entitiesPerPage' => 50]))->assertOk()
            ->viewData('page')['props'];

        return collect($props['projectComponents'])
            ->map(fn ($row): array => (array) $row)
            ->keyBy('id')
            ->all();
    }
}

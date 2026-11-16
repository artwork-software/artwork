<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Budget aus einem anderen Projekt übernehmen: das eigene Budget wurde gelöscht, bevor feststand, ob das
 * Quellprojekt überhaupt eines hat (Datenverlust + 500).
 */
final class BudgetFromProjectTest extends FeatureTestCase
{
    private function createTable(int $projectId, string $mainPositionName): Table
    {
        $table = Table::factory()->create(['project_id' => $projectId, 'is_template' => false]);
        Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'name' => 'KTO']);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id, 'name' => $mainPositionName]);
        SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);

        return $table;
    }

    #[Test]
    public function source_project_without_budget_keeps_the_own_budget(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $ownTable = $this->createTable($project->id, 'Eigenes');
        $sourceWithoutBudget = Project::factory()->create();

        $this->post(route('project.budget.template.project'), [
            'template_project_id' => $sourceWithoutBudget->id,
            'project_id' => $project->id,
        ])->assertSessionHasErrors('template_project_id');

        self::assertNotNull(Table::query()->find($ownTable->id));
        self::assertSame(['Eigenes'], $project->table()->firstOrFail()->mainPositions()->pluck('name')->all());
    }

    #[Test]
    public function budget_of_the_source_project_replaces_the_own_budget(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $ownTable = $this->createTable($project->id, 'Eigenes');
        $source = Project::factory()->create();
        $this->createTable($source->id, 'Quelle');

        $this->post(route('project.budget.template.project'), [
            'template_project_id' => (string) $source->id,
            'project_id' => $project->id,
        ])->assertSessionHasNoErrors();

        self::assertNull(Table::query()->find($ownTable->id));
        self::assertSame(['Quelle'], $project->table()->firstOrFail()->mainPositions()->pluck('name')->all());
    }

    #[Test]
    public function the_own_project_as_source_changes_nothing(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $ownTable = $this->createTable($project->id, 'Eigenes');

        $this->post(route('project.budget.template.project'), [
            'template_project_id' => (string) $project->id,
            'project_id' => $project->id,
        ])->assertSessionHasNoErrors();

        self::assertNotNull(Table::query()->find($ownTable->id));
    }
}

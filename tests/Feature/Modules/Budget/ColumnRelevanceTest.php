<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ColumnRelevanceTest extends FeatureTestCase
{
    private function createTableWithColumns(): Table
    {
        $table = Table::factory()->create(['is_template' => false]);

        // strukturelle Spalten (KTO/KST/Position)
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }

        Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);

        return $table->refresh();
    }

    private function relevantColumnIds(Table $table): array
    {
        return $table->columns()
            ->where('relevant_for_project_groups', true)
            ->pluck('id')
            ->all();
    }

    #[Test]
    public function adding_an_empty_column_moves_the_budget_relevant_flag_to_it(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ]);

        $newColumn = $table->columns()->orderByDesc('position')->first();
        $this->assertSame([$newColumn->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function adding_a_sum_column_does_not_move_the_budget_relevant_flag(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $relevantColumnId = $this->relevantColumnIds($table)[0];
        $secondValueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4]);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'sum',
            'first_column_id' => $relevantColumnId,
            'second_column_id' => $secondValueColumn->id,
        ]);

        $this->assertSame([$relevantColumnId], $this->relevantColumnIds($table));
    }

    #[Test]
    public function duplicating_a_column_moves_the_budget_relevant_flag_to_the_copy(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $relevantColumn = $table->columns()->where('relevant_for_project_groups', true)->first();

        $this->post(route('project.budget.column.duplicate', ['column' => $relevantColumn->id]));

        $copy = $table->columns()->where('name', 'like', '%(Kopie)')->first();
        $this->assertNotNull($copy);
        $this->assertSame([$copy->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function duplicating_a_sum_column_never_creates_a_flagged_copy(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $relevantColumnId = $this->relevantColumnIds($table)[0];
        $sumColumn = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 4,
            'type' => 'sum',
            // absichtlich inkonsistent geflaggt, um das Abräumen beim Replicate zu prüfen
            'relevant_for_project_groups' => true,
        ]);

        $this->post(route('project.budget.column.duplicate', ['column' => $sumColumn->id]));

        $copy = $table->columns()->where('name', 'like', '%(Kopie)')->first();
        $this->assertFalse($copy->relevant_for_project_groups);
    }

    #[Test]
    public function deleting_the_flagged_column_moves_the_flag_to_the_rightmost_value_column(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $otherValueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4]);
        $flagged = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 5,
            'relevant_for_project_groups' => true,
        ]);
        app(ColumnRelevanceService::class)->assignExclusive($flagged);

        $this->delete(route('project.budget.column.delete', ['column' => $flagged->id]));

        $this->assertDatabaseMissing('columns', ['id' => $flagged->id, 'deleted_at' => null]);
        $this->assertSame([$otherValueColumn->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function the_last_value_column_is_emptied_instead_of_deleted(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $lastValueColumn = $table->columns()->where('position', 3)->first();
        $cell = $lastValueColumn->cells()->create([
            'sub_position_row_id' => \Artwork\Modules\Budget\Models\SubPositionRow::factory()->create()->id,
            'value' => '1234',
            'verified_value' => null,
            'commented' => false,
        ]);

        $this->delete(route('project.budget.column.delete', ['column' => $lastValueColumn->id]));

        $this->assertDatabaseHas('columns', ['id' => $lastValueColumn->id]);
        $this->assertSame('0', $cell->refresh()->value);
        $this->assertSame([$lastValueColumn->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function marking_a_column_as_relevant_clears_the_flag_on_all_other_columns(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $newColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4]);

        $this->patch(route('project.budget.column.update.relevant', ['column' => $newColumn->id]));

        $this->assertSame([$newColumn->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function a_sum_column_cannot_be_marked_as_relevant(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $sumColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'type' => 'sum']);

        $this->patch(route('project.budget.column.update.relevant', ['column' => $sumColumn->id]))
            ->assertStatus(422);
    }

    #[Test]
    public function a_structural_column_cannot_be_marked_as_relevant(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $structuralColumn = $table->columns()->where('position', 0)->first();

        $this->patch(route('project.budget.column.update.relevant', ['column' => $structuralColumn->id]))
            ->assertStatus(422);
    }

    #[Test]
    public function ensure_single_relevant_column_repairs_multiple_flags(): void
    {
        $table = $this->createTableWithColumns();
        $secondFlagged = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 4,
            'relevant_for_project_groups' => true,
        ]);

        app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);

        // die hinterste geflaggte Wertspalte behält das Flag
        $this->assertSame([$secondFlagged->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function ensure_single_relevant_column_repairs_missing_flag(): void
    {
        $table = Table::factory()->create(['is_template' => false]);
        foreach ([0, 1, 2, 3, 4] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }

        app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);

        $flagged = $table->columns()->where('relevant_for_project_groups', true)->get();
        $this->assertCount(1, $flagged);
        $this->assertSame(4, $flagged->first()->position);
    }

    #[Test]
    public function ensure_single_relevant_column_removes_flags_from_non_value_columns(): void
    {
        $table = $this->createTableWithColumns();
        $flaggedValueColumnId = $this->relevantColumnIds($table)[0];
        Column::factory()->create([
            'table_id' => $table->id,
            'position' => 4,
            'type' => 'difference',
            'relevant_for_project_groups' => true,
        ]);

        app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);

        $this->assertSame([$flaggedValueColumnId], $this->relevantColumnIds($table));
    }

    #[Test]
    public function restoring_a_column_does_not_steal_the_budget_relevant_flag(): void
    {
        $this->actingAsAdmin();
        $table = $this->createTableWithColumns();
        $otherValueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4]);
        $flagged = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 5,
            'relevant_for_project_groups' => true,
        ]);
        app(ColumnRelevanceService::class)->assignExclusive($flagged);

        // Löschen verschiebt das Flag auf die hinterste verbleibende Wertspalte
        $this->delete(route('project.budget.column.delete', ['column' => $flagged->id]));
        $this->assertSame([$otherValueColumn->id], $this->relevantColumnIds($table));

        // Wiederherstellen darf das Flag nicht zurückholen
        $this->patch(route('project.budget.column.restore', ['column' => $flagged->id]));

        $this->assertNull($flagged->refresh()->deleted_at);
        $this->assertSame([$otherValueColumn->id], $this->relevantColumnIds($table));
    }

    #[Test]
    public function project_groups_get_a_budget_relevant_column_on_creation(): void
    {
        $project = Project::factory()->create(['is_group' => true]);

        app(BudgetService::class)->generateBasicBudgetValues($project);

        $table = $project->table()->first();
        $flagged = $table->columns()->where('relevant_for_project_groups', true)->get();
        $this->assertCount(1, $flagged);
        $this->assertSame('empty', $flagged->first()->type);
    }
}

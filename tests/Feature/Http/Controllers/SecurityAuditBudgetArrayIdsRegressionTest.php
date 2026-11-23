<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Audit 18.11.2026: Budget-Ids als Array (M1) und Planungstermine im Dashboard (N5).
 */
final class SecurityAuditBudgetArrayIdsRegressionTest extends FeatureTestCase
{
    /**
     * @return array{table: Table, column: Column, row: SubPositionRow, cell: ColumnCell}
     */
    private function createBudgetWithCell(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $column = Column::factory()->create(['table_id' => $table->id, 'position' => 3]);
        $cell = ColumnCell::factory()->create([
            'sub_position_row_id' => $row->id,
            'column_id' => $column->id,
            'value' => 50,
        ]);

        return ['table' => $table->refresh(), 'column' => $column, 'row' => $row, 'cell' => $cell];
    }

    private function userWithBudgetAccess(Project $project): User
    {
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        return $user;
    }

    private function createCalculation(ColumnCell $cell, int $value): CellCalculation
    {
        return CellCalculation::create([
            'cell_id' => $cell->id,
            'name' => 'Posten',
            'value' => $value,
            'description' => '',
            'position' => 1,
        ]);
    }

    // ---------- M1: Ids als Array ----------

    #[Test]
    public function cell_calculations_of_a_foreign_cell_cannot_be_wiped_with_an_array_cell_id(): void
    {
        $own = $this->createBudgetWithCell();
        $foreign = $this->createBudgetWithCell();
        $foreignCalculation = $this->createCalculation($foreign['cell'], 50);
        $this->userWithBudgetAccess($own['table']->project);

        $this->patchJson(route('project.budget.cell-calculation.update'), [
            'project_id' => $own['table']->project_id,
            'cell_id' => [$foreign['cell']->id],
            'calculations' => [],
        ])->assertStatus(422);

        $this->assertDatabaseHas('cell_calculations', ['id' => $foreignCalculation->id, 'deleted_at' => null]);
        $this->assertEquals(50, $foreign['cell']->refresh()->value);
    }

    #[Test]
    public function cell_calculations_of_a_foreign_cell_stay_forbidden_with_a_scalar_cell_id(): void
    {
        $own = $this->createBudgetWithCell();
        $foreign = $this->createBudgetWithCell();
        $this->userWithBudgetAccess($own['table']->project);

        $this->patchJson(route('project.budget.cell-calculation.update'), [
            'project_id' => $own['table']->project_id,
            'cell_id' => $foreign['cell']->id,
            'calculations' => [],
        ])->assertForbidden();
    }

    #[Test]
    public function cell_calculations_require_an_integer_cell_id_of_an_existing_cell(): void
    {
        $own = $this->createBudgetWithCell();
        $this->userWithBudgetAccess($own['table']->project);

        $this->patchJson(route('project.budget.cell-calculation.update'), [
            'project_id' => $own['table']->project_id,
            'cell_id' => 'abc',
            'calculations' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('cell_id');

        $this->patchJson(route('project.budget.cell-calculation.update'), [
            'project_id' => $own['table']->project_id,
            'cell_id' => ColumnCell::max('id') + 1000,
            'calculations' => [['name' => 'Neu', 'value' => 10]],
        ])->assertNotFound();

        $this->assertDatabaseMissing('cell_calculations', ['name' => 'Neu']);
    }

    #[Test]
    public function cell_calculations_of_an_own_cell_are_still_saved(): void
    {
        $own = $this->createBudgetWithCell();
        $kept = $this->createCalculation($own['cell'], 20);
        $removed = $this->createCalculation($own['cell'], 30);
        $this->userWithBudgetAccess($own['table']->project);

        $this->patchJson(route('project.budget.cell-calculation.update'), [
            'cell_id' => $own['cell']->id,
            'calculations' => [
                ['id' => $kept->id, 'cell_id' => $own['cell']->id, 'name' => 'Alt', 'value' => 25, 'position' => 1],
                ['cell_id' => $own['cell']->id, 'name' => 'Neu', 'value' => 10, 'position' => 2],
            ],
        ])->assertSuccessful()->assertJsonPath('success', true);

        $this->assertDatabaseHas('cell_calculations', ['id' => $kept->id, 'name' => 'Alt', 'deleted_at' => null]);
        $this->assertDatabaseHas('cell_calculations', ['cell_id' => $own['cell']->id, 'name' => 'Neu']);
        $this->assertNull(CellCalculation::find($removed->id));
        $this->assertEquals(35, $own['cell']->refresh()->value);
    }

    #[Test]
    public function sum_details_of_a_foreign_column_cannot_be_read_with_an_array_column_id(): void
    {
        $own = $this->createBudgetWithCell();
        $foreign = $this->createBudgetWithCell();
        $this->userWithBudgetAccess($own['table']->project);

        $this->getJson(route('project.budget.sum-details.show', [
            'project_id' => $own['table']->project_id,
            'type' => 'budget',
            'position_id' => 'COST',
            'column_id' => [$foreign['column']->id],
        ]))->assertStatus(422);

        $this->getJson(route('project.budget.sum-details.show', [
            'type' => 'budget',
            'position_id' => 'COST',
            'column_id' => $own['column']->id,
        ]))->assertSuccessful();
    }

    #[Test]
    public function project_ids_and_position_ids_sent_as_array_are_rejected(): void
    {
        $own = $this->createBudgetWithCell();
        $foreign = $this->createBudgetWithCell();
        $this->userWithBudgetAccess($own['table']->project);

        $this->postJson(route('project.budget.template.project'), [
            'project_id' => [$own['table']->project_id],
            'template_project_id' => $own['table']->project_id,
        ])->assertStatus(422);

        $this->postJson(route('project.budget.template.project'), [
            'project_id' => $own['table']->project_id,
            'template_project_id' => [$foreign['table']->project_id],
        ])->assertStatus(422);

        $this->actingAsUserWith('can add and remove verified states', auth()->user());
        $foreignMain = MainPosition::where('table_id', $foreign['table']->id)->firstOrFail();

        $this->postJson(route('project.budget.verified.main-position.request'), [
            'project_id' => $own['table']->project_id,
            'id' => [$foreignMain->id],
        ])->assertStatus(422);
    }

    #[Test]
    public function linked_columns_of_a_new_column_must_belong_to_an_accessible_budget(): void
    {
        $own = $this->createBudgetWithCell();
        $foreign = $this->createBudgetWithCell();
        $secondOwnColumn = Column::factory()->create(['table_id' => $own['table']->id, 'position' => 4]);
        $this->userWithBudgetAccess($own['table']->project);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $own['table']->id,
            'column_type' => 'sum',
            'first_column_id' => $own['column']->id,
            'second_column_id' => $foreign['column']->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('columns', ['linked_second_column' => $foreign['column']->id]);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $own['table']->id,
            'column_type' => 'sum',
            'first_column_id' => $own['column']->id,
            'second_column_id' => $secondOwnColumn->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('columns', [
            'table_id' => $own['table']->id,
            'type' => 'sum',
            'linked_first_column' => $own['column']->id,
            'linked_second_column' => $secondOwnColumn->id,
        ]);
    }

    // ---------- N5: Planungstermine im Dashboard ----------

    #[Test]
    public function dashboard_shows_planning_events_only_with_planning_calendar_permission(): void
    {
        $project = Project::factory()->create();
        $fixedEvent = Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => now()->startOfDay()->addHours(10),
            'end_time' => now()->startOfDay()->addHours(11),
            'is_planning' => false,
        ]);
        $planningEvent = Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => now()->startOfDay()->addHours(12),
            'end_time' => now()->startOfDay()->addHours(13),
            'is_planning' => true,
        ]);

        $user = User::factory()->create();
        $project->users()->attach($user->id);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertInertia(
            fn (AssertableInertia $page) => $page->where(
                'eventsOfDay',
                fn ($events) => collect($events)->pluck('id')->all() === [$fixedEvent->id]
            )
        );

        $this->actingAsUserWith('can see planning calendar', $user);

        $this->get(route('dashboard'))->assertInertia(
            fn (AssertableInertia $page) => $page->where(
                'eventsOfDay',
                fn ($events) => collect($events)->pluck('id')->sort()->values()->all()
                    === [$fixedEvent->id, $planningEvent->id]
            )
        );
    }
}

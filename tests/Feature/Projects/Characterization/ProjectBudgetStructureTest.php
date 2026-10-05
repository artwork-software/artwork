<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Struktur-Endpunkte der Projekt-Budgettabelle (Haupt-/Unterpositionen,
 * Zeilen, Spalten, Tabelle, Kommentiert-Status, Sperren/Festschreiben). Autorisierung läuft
 * ausschließlich über EnsureUserCanAccessProjectBudget (access_budget am Projekt) bzw. zusätzlich
 * "can add and remove verified states".
 */
final class ProjectBudgetStructureTest extends FeatureTestCase
{
    private function projectWithBudget(): Project
    {
        $project = Project::factory()->create();
        app(BudgetService::class)->generateBasicBudgetValues($project);

        return $project;
    }

    private function table(Project $project): Table
    {
        return Table::query()->where('project_id', $project->id)->sole();
    }

    private function actingAsBudgetMember(Project $project, array $permissions = []): User
    {
        $user = $permissions === [] ? User::factory()->create() : $this->actingAsUserWith($permissions);
        $project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        return $user;
    }

    private function firstSubPosition(Table $table): SubPosition
    {
        return SubPosition::query()
            ->whereIn('main_position_id', $table->mainPositions()->pluck('id'))
            ->orderBy('id')
            ->firstOrFail();
    }

    #[Test]
    public function budget_member_can_add_main_position_with_sub_position_and_row(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);
        $mainPositionsBefore = $table->mainPositions()->count();

        $this->post(route('project.budget.main-position.add'), [
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
            'positionBefore' => 0,
        ])->assertOk();

        $this->assertSame($mainPositionsBefore + 1, $table->mainPositions()->count());
        $newMainPosition = $table->mainPositions()->where('name', 'Neue Hauptposition')->sole();
        $this->assertSame(1, $newMainPosition->position);
        $subPosition = $newMainPosition->subPositions()->sole();
        $this->assertSame('Neue Unterposition', $subPosition->name);
        $row = $subPosition->subPositionRows()->sole();
        $this->assertSame($table->columns()->count(), $row->cells()->count());
    }

    #[Test]
    public function project_member_without_budget_access_cannot_add_main_position(): void
    {
        $project = $this->projectWithBudget();
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true, 'access_budget' => false]);
        $this->actingAs($user);
        $table = $this->table($project);
        $before = $table->mainPositions()->count();

        $this->post(route('project.budget.main-position.add'), [
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
            'positionBefore' => 0,
        ])->assertForbidden();

        $this->assertSame($before, $table->mainPositions()->count());
    }

    #[Test]
    public function budget_access_on_own_project_does_not_unlock_foreign_column(): void
    {
        $ownProject = $this->projectWithBudget();
        $foreignProject = $this->projectWithBudget();
        $this->actingAsBudgetMember($ownProject);
        $foreignColumn = $this->table($foreignProject)->columns()->orderBy('position')->first();

        $this->patch(route('project.budget.column.update-name'), [
            'table_id' => $this->table($ownProject)->id,
            'column_id' => $foreignColumn->id,
            'columnName' => 'Gekapert',
        ])->assertForbidden();

        $this->assertNotSame('Gekapert', $foreignColumn->fresh()->name);
    }

    #[Test]
    public function budget_member_can_add_sub_position_and_row(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);
        $mainPosition = $table->mainPositions()->orderBy('id')->first();
        $subPositionsBefore = $mainPosition->subPositions()->count();

        $this->post(route('project.budget.sub-position.add'), [
            'table_id' => $table->id,
            'main_position_id' => $mainPosition->id,
            'positionBefore' => 0,
        ])->assertOk();

        $this->assertSame($subPositionsBefore + 1, $mainPosition->subPositions()->count());
        $subPosition = $mainPosition->subPositions()->where('name', 'Neue Unterposition')->sole();
        $rowsBefore = $subPosition->subPositionRows()->count();

        $this->post(route('project.budget.sub-position-row.add'), [
            'table_id' => $table->id,
            'sub_position_id' => $subPosition->id,
            'positionBefore' => 1,
        ])->assertOk();

        $this->assertSame($rowsBefore + 1, $subPosition->subPositionRows()->count());
        $newRow = $subPosition->subPositionRows()->where('position', 2)->sole();
        $this->assertSame(
            ['-', '-', '-'],
            $newRow->cells()->orderBy('id')->limit(3)->pluck('value')->all()
        );
    }

    #[Test]
    public function budget_member_can_rename_table_column_main_and_sub_position(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);
        $column = $table->columns()->where('position', 3)->first();
        $mainPosition = $table->mainPositions()->orderBy('id')->first();
        $subPosition = $mainPosition->subPositions()->first();

        $this->patch(route('project.budget.table.update-name'), [
            'table_id' => $table->id,
            'table_name' => 'Spielzeit-Budget',
        ])->assertOk();
        $this->patch(route('project.budget.column.update-name'), [
            'column_id' => $column->id,
            'columnName' => 'Plan 2027',
        ])->assertOk();
        $this->patch(route('project.budget.main-position.update-name'), [
            'mainPosition_id' => $mainPosition->id,
            'mainPositionName' => 'Personal',
        ])->assertOk();
        $this->patch(route('project.budget.sub-position.update-name'), [
            'subPosition_id' => $subPosition->id,
            'subPositionName' => 'Gagen',
        ])->assertOk();

        $this->assertSame('Spielzeit-Budget', $table->fresh()->name);
        $this->assertSame('Plan 2027', $column->fresh()->name);
        $this->assertSame('Personal', $mainPosition->fresh()->name);
        $this->assertSame('Gagen', $subPosition->fresh()->name);
    }

    #[Test]
    public function renaming_unknown_column_or_table_returns_404_for_admin(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('project.budget.column.update-name'), [
            'column_id' => 999999999,
            'columnName' => 'x',
        ])->assertNotFound();
        $this->patch(route('project.budget.table.update-name'), [
            'table_id' => 999999999,
            'table_name' => 'x',
        ])->assertNotFound();
    }

    #[Test]
    public function budget_member_can_change_column_color(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $column = $this->table($project)->columns()->where('position', 3)->first();

        $this->patch(route('project.budget.column-color.change'), [
            'columnId' => $column->id,
            'color' => 'greenColumn',
        ])->assertOk();

        $this->assertSame('greenColumn', $column->fresh()->color);
    }

    #[Test]
    public function commented_status_of_row_propagates_to_value_cells_only(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $row = SubPositionRow::query()
            ->where('sub_position_id', $this->firstSubPosition($this->table($project))->id)
            ->firstOrFail();

        $this->patch(route('project.budget.row.commented', $row), ['commented' => true])->assertOk();

        $this->assertTrue((bool) $row->fresh()->commented);
        $cells = $row->cells()->orderBy('id')->get();
        $this->assertSame([false, false, false], $cells->take(3)->map(fn ($c) => (bool) $c->commented)->all());
        $this->assertTrue($cells->skip(3)->every(fn ($c) => (bool) $c->commented));
    }

    #[Test]
    public function budget_member_can_toggle_commented_status_of_cell_and_column(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $column = $this->table($project)->columns()->where('position', 3)->first();
        $cell = ColumnCell::query()->where('column_id', $column->id)->firstOrFail();

        $this->patch(route('project.budget.cell.commented', $cell), ['commented' => true])->assertOk();
        $this->patch(route('project.budget.column.update.commented', $column), ['commented' => true])->assertOk();

        $this->assertTrue((bool) $cell->fresh()->commented);
        $this->assertTrue((bool) $column->fresh()->commented);
    }

    #[Test]
    public function outsider_cannot_toggle_commented_status_of_cell(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAs(User::factory()->create());
        $cell = ColumnCell::query()
            ->whereIn('column_id', $this->table($project)->columns()->pluck('id'))
            ->firstOrFail();

        $this->patch(route('project.budget.cell.commented', $cell), ['commented' => true])->assertForbidden();

        $this->assertFalse((bool) $cell->fresh()->commented);
    }

    #[Test]
    public function budget_member_can_delete_row_sub_position_and_main_position(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);
        $subPosition = $this->firstSubPosition($table);
        $row = $subPosition->subPositionRows()->firstOrFail();

        $this->delete(route('project.budget.sub-position-row.delete', $row))->assertRedirect();
        $this->assertNull(SubPositionRow::withTrashed()->find($row->id));

        $this->delete(route('project.budget.sub-position.delete', $subPosition))->assertRedirect();
        $this->assertNull(SubPosition::withTrashed()->find($subPosition->id));

        $mainPosition = $table->mainPositions()->orderBy('id')->firstOrFail();
        $this->delete(route('project.budget.main-position.delete', $mainPosition))->assertRedirect();
        $this->assertNull(MainPosition::withTrashed()->find($mainPosition->id));
    }

    #[Test]
    public function outsider_cannot_delete_main_position(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAs(User::factory()->create());
        $mainPosition = $this->table($project)->mainPositions()->firstOrFail();

        $this->delete(route('project.budget.main-position.delete', $mainPosition))->assertForbidden();

        $this->assertNotNull($mainPosition->fresh());
    }

    #[Test]
    public function trashed_columns_lists_soft_deleted_columns_of_table(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);
        $trashed = Column::factory()->create(['table_id' => $table->id, 'name' => 'Alt', 'position' => 9]);
        $trashed->delete();

        $response = $this->getJson(route('project.budget.column.trashed', $table));

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $trashed->id)
            ->assertJsonPath('0.name', 'Alt');
    }

    #[Test]
    public function budget_member_can_soft_delete_table(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);

        $this->delete(route('project.budget.table.soft.delete', $table))->assertRedirect();

        $this->assertSoftDeleted('tables', ['id' => $table->id]);
    }

    #[Test]
    public function restoring_a_soft_deleted_table_brings_it_back(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);
        $this->delete(route('project.budget.table.soft.delete', $table))->assertRedirect();

        // vorher 500: withTrashed() auf SageAssignedData ohne SoftDeletes
        $this->patch(route('project.budget.table.restore', $table->id))->assertRedirect();

        $this->assertNotSoftDeleted('tables', ['id' => $table->id]);
    }

    #[Test]
    public function budget_member_can_force_delete_table(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $table = $this->table($project);

        $this->delete(route('project.budget.table.delete', $table))->assertRedirect();

        $this->assertNull(Table::withTrashed()->find($table->id));
    }

    #[Test]
    public function reset_table_replaces_budget_with_fresh_basic_table(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $oldTable = $this->table($project);
        $oldTable->update(['name' => 'Bearbeitet']);

        $this->patch(route('project.budget.reset.table', $project))->assertOk();

        $this->assertNull(Table::withTrashed()->find($oldTable->id));
        $newTable = $this->table($project);
        $this->assertSame($project->name . ' Budgettabelle', $newTable->name);
        $this->assertSame(2, $newTable->mainPositions()->count());
    }

    #[Test]
    public function outsider_cannot_reset_table(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAs(User::factory()->create());
        $table = $this->table($project);

        $this->patch(route('project.budget.reset.table', $project))->assertForbidden();

        $this->assertNotNull($table->fresh());
    }

    #[Test]
    public function lock_and_unlock_column_need_verified_states_permission(): void
    {
        $project = $this->projectWithBudget();
        $user = $this->actingAsBudgetMember($project, [PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value]);
        $column = $this->table($project)->columns()->where('position', 3)->first();

        $this->patch(route('project.budget.lock.column'), ['columnId' => $column->id])->assertOk();
        $column->refresh();
        $this->assertTrue((bool) $column->is_locked);
        $this->assertSame($user->id, $column->locked_by);

        $this->patch(route('project.budget.unlock.column'), ['columnId' => $column->id])->assertOk();
        $column->refresh();
        $this->assertFalse((bool) $column->is_locked);
        $this->assertNull($column->locked_by);
    }

    #[Test]
    public function budget_member_without_verified_states_permission_cannot_lock_column(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project);
        $column = $this->table($project)->columns()->where('position', 3)->first();

        $this->patch(route('project.budget.lock.column'), ['columnId' => $column->id])->assertForbidden();

        $this->assertFalse((bool) $column->fresh()->is_locked);
    }

    #[Test]
    public function fix_and_unfix_main_position_toggle_is_fixed(): void
    {
        $project = $this->projectWithBudget();
        $this->actingAsBudgetMember($project, [PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value]);
        $mainPosition = $this->table($project)->mainPositions()->orderBy('id')->first();

        $this->patch(route('project.budget.fix.main-position'), [
            'mainPositionId' => $mainPosition->id,
            'project_id' => $project->id,
        ])->assertOk();
        $this->assertTrue((bool) $mainPosition->fresh()->is_fixed);

        $this->patch(route('project.budget.unfix.main-position'), [
            'mainPositionId' => $mainPosition->id,
            'project_id' => $project->id,
        ])->assertOk();
        $this->assertFalse((bool) $mainPosition->fresh()->is_fixed);
    }
}

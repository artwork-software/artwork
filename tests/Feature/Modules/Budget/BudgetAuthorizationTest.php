<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetAuthorizationTest extends FeatureTestCase
{
    private function createProjectTableWithColumn(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }
        $column = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);

        return [$table->refresh(), $column];
    }

    #[Test]
    public function user_without_budget_access_cannot_mutate_budget(): void
    {
        [$table, $column] = $this->createProjectTableWithColumn();
        $this->actingAs(User::factory()->create());

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ])->assertForbidden();

        $this->delete(route('project.budget.column.delete', ['column' => $column->id]))
            ->assertForbidden();

        $this->patch(route('project.budget.cell.update'), [
            'column_id' => $column->id,
            'sub_position_row_id' => 1,
            'value' => '100',
        ])->assertForbidden();
    }

    #[Test]
    public function user_with_project_budget_access_can_mutate_budget(): void
    {
        [$table] = $this->createProjectTableWithColumn();
        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ])->assertSuccessful();
    }

    #[Test]
    public function budget_access_on_one_project_does_not_grant_access_to_another(): void
    {
        [$tableA] = $this->createProjectTableWithColumn();
        [$tableB] = $this->createProjectTableWithColumn();

        $user = User::factory()->create();
        $tableA->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $tableB->id,
            'column_type' => 'empty',
        ])->assertForbidden();
    }

    #[Test]
    public function own_project_id_cannot_smuggle_a_foreign_column_past_the_check(): void
    {
        [$tableA] = $this->createProjectTableWithColumn();
        [, $foreignColumn] = $this->createProjectTableWithColumn();

        $user = User::factory()->create();
        $tableA->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        // First-Match-Bypass: eigene project_id im Body, aber column_id eines
        // fremden Projekts — JEDE referenzierte Id muss autorisiert sein.
        $this->patch(route('project.budget.cell.update'), [
            'project_id' => $tableA->project_id,
            'column_id' => $foreignColumn->id,
            'sub_position_row_id' => 1,
            'value' => '100',
        ])->assertForbidden();
    }

    #[Test]
    public function project_budget_user_can_reorder_sub_position_rows(): void
    {
        [$table] = $this->createProjectTableWithColumn();
        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $rowOne = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id, 'position' => 0]);
        $rowTwo = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id, 'position' => 1]);

        // Verschachtelter Payload ohne Top-Level-Id darf nicht in 403 laufen.
        $this->patch(route('project.budget.sub-position-row.reorder'), [
            'updates' => [
                ['sub_position_id' => $subPosition->id, 'row_ids' => [$rowTwo->id, $rowOne->id]],
            ],
        ])->assertRedirect();
    }

    #[Test]
    public function foreign_sub_position_rows_cannot_be_reordered(): void
    {
        [$tableA] = $this->createProjectTableWithColumn();
        [$tableB] = $this->createProjectTableWithColumn();
        $user = User::factory()->create();
        $tableA->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $foreignMain = MainPosition::factory()->create(['table_id' => $tableB->id]);
        $foreignSub = SubPosition::factory()->create(['main_position_id' => $foreignMain->id]);
        $foreignRow = SubPositionRow::factory()->create(['sub_position_id' => $foreignSub->id, 'position' => 0]);

        $this->patch(route('project.budget.sub-position-row.reorder'), [
            'updates' => [
                ['sub_position_id' => $foreignSub->id, 'row_ids' => [$foreignRow->id]],
            ],
        ])->assertForbidden();
    }

    #[Test]
    public function project_budget_user_can_comment_on_sage_assigned_data(): void
    {
        [$table, $column] = $this->createProjectTableWithColumn();
        $user = User::factory()->create();
        // Policy-Voraussetzung der Kommentare (existiert unabhängig von der
        // Budget-Middleware); hier interessiert nur, dass die Middleware das
        // Projekt aus sageAssignedDataId auflöst statt mit 403 abzubrechen.
        $user->givePermissionTo(PermissionEnum::WRITE_PROJECTS->value);
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id, 'position' => 0]);
        $cell = ColumnCell::factory()->create(['column_id' => $column->id, 'sub_position_row_id' => $row->id]);
        $sageAssignedData = SageAssignedData::create([
            'column_cell_id' => $cell->id,
            'sage_id' => 4711,
            'tan' => 1,
            'periode' => 7,
            'kto_haben' => '4400',
            'kto_soll' => '6300',
            'sa_kto' => '1',
            'kst_traeger' => 'KT-1',
            'kst_stelle' => 'KS-1',
            'kreditor' => 'Testkreditor',
            'buchungstext' => 'Testbuchung',
            'buchungsbetrag' => '100',
            'belegnummer' => 'B-1',
            'belegdatum' => now()->toDateString(),
            'buchungsdatum' => now()->toDateString(),
        ]);

        // Payload enthält nur sageAssignedDataId — die Middleware muss das
        // Projekt darüber auflösen können, sonst 403 für legitime Nutzer.
        $this->post(route('sageAssignedDataComments.store'), [
            'userId' => $user->id,
            'sageAssignedDataId' => $sageAssignedData->id,
            'comment' => 'Rückfrage zur Buchung',
        ])->assertRedirect();
    }

    #[Test]
    public function verification_take_back_of_a_foreign_position_is_forbidden(): void
    {
        [$tableA] = $this->createProjectTableWithColumn();
        [$tableB] = $this->createProjectTableWithColumn();
        $user = User::factory()->create();
        $tableA->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $foreignMain = MainPosition::factory()->create(['table_id' => $tableB->id]);

        // {position: {id}, type} muss von der Middleware aufgelöst werden.
        $this->post(route('project.budget.take-back.verification'), [
            'type' => 'main',
            'position' => ['id' => $foreignMain->id],
        ])->assertForbidden();
    }

    #[Test]
    public function user_with_global_budget_permission_can_mutate_any_budget(): void
    {
        [$table] = $this->createProjectTableWithColumn();
        $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ])->assertSuccessful();
    }
}

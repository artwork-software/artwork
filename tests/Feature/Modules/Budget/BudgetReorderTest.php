<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * FIN-02: Drag & Drop auf allen drei Ebenen schreibt position/order und die
 * Fremdschlüssel (Wechsel der Haupt-/Unterposition) korrekt.
 */
final class BudgetReorderTest extends FeatureTestCase
{
    private function createTableWithBudgetUser(): Table
    {
        $table = Table::factory()->create(['is_template' => false]);
        foreach ([0, 1, 2, 3] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }

        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        return $table->refresh();
    }

    #[Test]
    public function main_positions_are_reordered_by_given_id_order(): void
    {
        $table = $this->createTableWithBudgetUser();
        $first = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $second = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 2]);
        $third = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 3]);

        $this->patch(route('project.budget.main-position.reorder'), [
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
            'main_position_ids' => [$third->id, $first->id, $second->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('main_positions', ['id' => $third->id, 'position' => 1]);
        $this->assertDatabaseHas('main_positions', ['id' => $first->id, 'position' => 2]);
        $this->assertDatabaseHas('main_positions', ['id' => $second->id, 'position' => 3]);
    }

    #[Test]
    public function sub_positions_are_reordered_and_moved_to_the_target_main_position(): void
    {
        $table = $this->createTableWithBudgetUser();
        $sourceMain = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $targetMain = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 2]);
        $staying = SubPosition::factory()->create(['main_position_id' => $targetMain->id, 'position' => 1]);
        $moved = SubPosition::factory()->create(['main_position_id' => $sourceMain->id, 'position' => 1]);

        $this->patch(route('project.budget.sub-position.reorder'), [
            'main_position_id' => $targetMain->id,
            'sub_position_ids' => [$moved->id, $staying->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('sub_positions', [
            'id' => $moved->id,
            'main_position_id' => $targetMain->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('sub_positions', [
            'id' => $staying->id,
            'main_position_id' => $targetMain->id,
            'position' => 2,
        ]);
    }

    #[Test]
    public function sub_position_rows_are_reordered_and_moved_to_the_target_sub_position(): void
    {
        $table = $this->createTableWithBudgetUser();
        $main = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $sourceSub = SubPosition::factory()->create(['main_position_id' => $main->id, 'position' => 1]);
        $targetSub = SubPosition::factory()->create(['main_position_id' => $main->id, 'position' => 2]);

        $movedRow = SubPositionRow::factory()->create(['sub_position_id' => $sourceSub->id, 'position' => 1, 'order' => 1]);
        $remainingRow = SubPositionRow::factory()->create(['sub_position_id' => $sourceSub->id, 'position' => 2, 'order' => 2]);
        $targetRow = SubPositionRow::factory()->create(['sub_position_id' => $targetSub->id, 'position' => 1, 'order' => 1]);

        // Quelle und Ziel werden wie im Frontend gemeinsam gesendet.
        $this->patch(route('project.budget.sub-position-row.reorder'), [
            'updates' => [
                ['sub_position_id' => $sourceSub->id, 'row_ids' => [$remainingRow->id]],
                ['sub_position_id' => $targetSub->id, 'row_ids' => [$targetRow->id, $movedRow->id]],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sub_position_rows', [
            'id' => $remainingRow->id,
            'sub_position_id' => $sourceSub->id,
            'position' => 1,
            'order' => 1,
        ]);
        $this->assertDatabaseHas('sub_position_rows', [
            'id' => $targetRow->id,
            'sub_position_id' => $targetSub->id,
            'position' => 1,
            'order' => 1,
        ]);
        $this->assertDatabaseHas('sub_position_rows', [
            'id' => $movedRow->id,
            'sub_position_id' => $targetSub->id,
            'position' => 2,
            'order' => 2,
        ]);
    }

    #[Test]
    public function sub_position_row_reorder_rejects_duplicate_row_ids(): void
    {
        $table = $this->createTableWithBudgetUser();
        $main = MainPosition::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $sub = SubPosition::factory()->create(['main_position_id' => $main->id, 'position' => 1]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $sub->id, 'position' => 1, 'order' => 1]);

        $this->from(route('projects'))->patch(route('project.budget.sub-position-row.reorder'), [
            'updates' => [
                ['sub_position_id' => $sub->id, 'row_ids' => [$row->id, $row->id]],
            ],
        ])->assertSessionHasErrors('updates');
    }

    #[Test]
    public function value_columns_are_reordered_and_relettered_while_fixed_columns_stay(): void
    {
        $table = $this->createTableWithBudgetUser();
        $columns = $table->columns()->orderBy('position')->get();
        $first = $columns[3];
        $second = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'type' => 'empty']);
        $third = Column::factory()->create(['table_id' => $table->id, 'position' => 5, 'type' => 'empty']);
        $sage = Column::factory()->create(['table_id' => $table->id, 'position' => 6, 'type' => 'sage']);

        $this->patch(route('project.budget.column.reorder'), [
            'table_id' => $table->id,
            'column_ids' => [$third->id, $first->id, $second->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('columns', ['id' => $third->id, 'position' => 3, 'subName' => 'A']);
        $this->assertDatabaseHas('columns', ['id' => $first->id, 'position' => 4, 'subName' => 'B']);
        $this->assertDatabaseHas('columns', ['id' => $second->id, 'position' => 5, 'subName' => 'C']);
        $this->assertDatabaseHas('columns', ['id' => $sage->id, 'position' => 6]);
        foreach ([0, 1, 2] as $index) {
            $this->assertDatabaseHas('columns', ['id' => $columns[$index]->id, 'position' => $index]);
        }
    }

    #[Test]
    public function column_reorder_rejects_fixed_foreign_or_incomplete_column_lists(): void
    {
        $table = $this->createTableWithBudgetUser();
        $columns = $table->columns()->orderBy('position')->get();
        $second = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'type' => 'empty']);
        $foreign = Column::factory()->create(['position' => 3, 'type' => 'empty']);

        // feste Spalte (Position) in der Liste
        $this->patch(route('project.budget.column.reorder'), [
            'table_id' => $table->id,
            'column_ids' => [$columns[2]->id, $columns[3]->id, $second->id],
        ])->assertStatus(422);

        // Spalte einer fremden Tabelle
        $this->patch(route('project.budget.column.reorder'), [
            'table_id' => $table->id,
            'column_ids' => [$second->id, $foreign->id],
        ])->assertStatus(422);

        // unvollständige Liste
        $this->patch(route('project.budget.column.reorder'), [
            'table_id' => $table->id,
            'column_ids' => [$second->id],
        ])->assertStatus(422);

        $this->assertDatabaseHas('columns', ['id' => $columns[3]->id, 'position' => 3]);
        $this->assertDatabaseHas('columns', ['id' => $second->id, 'position' => 4]);
    }

    #[Test]
    public function column_reorder_requires_budget_access(): void
    {
        $table = $this->createTableWithBudgetUser();
        $columns = $table->columns()->orderBy('position')->get();
        $second = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'type' => 'empty']);

        $this->actingAs(User::factory()->create());

        $this->patch(route('project.budget.column.reorder'), [
            'table_id' => $table->id,
            'column_ids' => [$second->id, $columns[3]->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('columns', ['id' => $second->id, 'position' => 4]);
    }
}

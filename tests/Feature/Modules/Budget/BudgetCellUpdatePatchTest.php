<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Zell-Update-Endpunkt liefert seit dem Budget-Revamp einen schlanken
 * JSON-Patch (Zellen der Zeile + neu berechnete Summen) statt einer leeren
 * Response - das Frontend patcht damit gezielt, ohne Full-Refetch.
 */
final class BudgetCellUpdatePatchTest extends FeatureTestCase
{
    /**
     * @return array{0: Table, 1: Column, 2: Column, 3: Column, 4: SubPositionRow, 5: SubPosition, 6: MainPosition}
     */
    private function createBudgetStructure(): array
    {
        $table = Table::factory()->create(['is_template' => false]);

        foreach ([0, 1, 2] as $position) {
            $firstColumn = Column::factory()->create([
                'table_id' => $table->id,
                'position' => $position,
                'type' => 'empty',
            ]);
        }

        $valueColumnA = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'type' => 'empty',
        ]);
        $valueColumnB = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 4,
            'type' => 'empty',
        ]);
        $differenceColumn = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 5,
            'type' => 'difference',
            'linked_first_column' => $valueColumnA->id,
            'linked_second_column' => $valueColumnB->id,
        ]);

        $mainPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
        ]);
        $subPosition = SubPosition::factory()->create([
            'main_position_id' => $mainPosition->id,
        ]);
        $row = SubPositionRow::factory()->create([
            'sub_position_id' => $subPosition->id,
        ]);

        foreach ($table->columns()->get() as $column) {
            ColumnCell::factory()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $row->id,
                'value' => in_array($column->position, [0, 1, 2], true) ? '-' : '0,00',
                'verified_value' => null,
                'commented' => false,
            ]);
        }

        return [$table->refresh(), $valueColumnA, $valueColumnB, $differenceColumn, $row, $subPosition, $mainPosition];
    }

    #[Test]
    public function cell_update_returns_patch_with_fresh_cells_and_sums(): void
    {
        [$table, $valueColumnA, , $differenceColumn, $row, $subPosition, $mainPosition] = $this->createBudgetStructure();

        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $response = $this->patchJson(route('project.budget.cell.update'), [
            'column_id' => $valueColumnA->id,
            'sub_position_row_id' => $row->id,
            'value' => '150,50',
            'is_verified' => false,
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'rowCells' => [['id', 'column_id', 'value', 'verified_value', 'commented']],
            'subPositionSums',
            'mainPositionSums',
            'tableSums' => [
                'costSums',
                'earningSums',
                'commentedCostSums',
                'commentedEarningSums',
                'costSumDetails',
                'earningSumDetails',
            ],
        ]);

        $patch = $response->json();

        $patchedCell = collect($patch['rowCells'])->firstWhere('column_id', $valueColumnA->id);
        self::assertNotNull($patchedCell);
        self::assertSame('150,50', $patchedCell['value']);

        // Automatische Differenz-Spalte wurde serverseitig neu berechnet
        $differenceCell = collect($patch['rowCells'])->firstWhere('column_id', $differenceColumn->id);
        self::assertNotNull($differenceCell);
        self::assertSame('150.50', $differenceCell['value']);

        self::assertArrayHasKey((string) $subPosition->id, $patch['subPositionSums']);
        self::assertArrayHasKey((string) $mainPosition->id, $patch['mainPositionSums']);
        self::assertArrayHasKey('columnSums', $patch['mainPositionSums'][(string) $mainPosition->id]);
        self::assertArrayHasKey('columnVerifiedChanges', $patch['mainPositionSums'][(string) $mainPosition->id]);
    }

    #[Test]
    public function cell_update_for_unknown_cell_returns_not_found(): void
    {
        [$table, $valueColumnA] = $this->createBudgetStructure();

        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $this->patchJson(route('project.budget.cell.update'), [
            'column_id' => $valueColumnA->id,
            'sub_position_row_id' => 999999,
            'value' => '1',
            'is_verified' => false,
        ])->assertNotFound();
    }
}

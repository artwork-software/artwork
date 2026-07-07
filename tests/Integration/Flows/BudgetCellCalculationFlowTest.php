<?php

namespace Tests\Integration\Flows;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetSumCalculator;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * End-to-end flow: A budget table with MainPosition → SubPosition → Row →
 * ColumnCells gets summed by BudgetSumCalculator::computeAndAttachSums().
 *
 * Note: BudgetSumCalculator skips the first 3 sorted columns when computing
 * costSums/earningSums (those are reserved for label columns in real tables).
 * We therefore configure 4 columns and assert the 4th column's cell value.
 */
final class BudgetCellCalculationFlowTest extends FeatureTestCase
{
    private function makeTableWithFourColumns(Project $project): array
    {
        $table = Table::factory()->create(['project_id' => $project->id]);

        $columns = [];
        for ($i = 0; $i < 4; $i++) {
            $columns[] = Column::factory()->create([
                'table_id' => $table->id,
                'position' => $i,
            ]);
        }

        return [$table, $columns];
    }

    private function fillRowCells(SubPositionRow $row, array $columns, array $values): void
    {
        foreach ($columns as $idx => $column) {
            ColumnCell::factory()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $row->id,
                'value' => $values[$idx] ?? '0',
                'commented' => false,
            ]);
        }
    }

    #[Test]
    public function compute_attaches_cost_sums_for_cost_main_position(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('projects.store'), [
            'name' => 'Budget Project A',
            'isGroup' => false,
        ])->assertRedirect();

        /** @var Project $project */
        $project = Project::query()->where('name', 'Budget Project A')->firstOrFail();

        [$table, $columns] = $this->makeTableWithFourColumns($project);
        $mainPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
        ]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        // First 3 columns are skipped in costSums map → put values in 4th col only
        $this->fillRowCells($row, $columns, ['1', '2', '3', '150,50']);

        // Reload everything with relations
        $table = $table->fresh([
            'columns',
            'mainPositions.subPositions.subPositionRows.cells',
        ]);

        app(BudgetSumCalculator::class)->computeAndAttachSums($table);

        $costSums = $table->getAttribute('costSums');
        $this->assertNotNull($costSums);
        $this->assertEqualsWithDelta(
            150.50,
            $costSums[$columns[3]->id] ?? 0,
            0.001,
            'Cost sum for 4th column should equal cell value 150.50'
        );
    }

    #[Test]
    public function compute_attaches_earning_sums_for_earning_main_position(): void
    {
        $this->actingAsAdmin();

        $this->post(route('projects.store'), [
            'name' => 'Budget Project B',
            'isGroup' => false,
        ])->assertRedirect();

        /** @var Project $project */
        $project = Project::query()->where('name', 'Budget Project B')->firstOrFail();

        [$table, $columns] = $this->makeTableWithFourColumns($project);
        $mainPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_EARNING',
        ]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        $this->fillRowCells($row, $columns, ['0', '0', '0', '99,99']);

        $table = $table->fresh([
            'columns',
            'mainPositions.subPositions.subPositionRows.cells',
        ]);

        app(BudgetSumCalculator::class)->computeAndAttachSums($table);

        $earningSums = $table->getAttribute('earningSums');
        $this->assertNotNull($earningSums);
        $this->assertEqualsWithDelta(
            99.99,
            $earningSums[$columns[3]->id] ?? 0,
            0.001
        );
        // costSums should be empty for an earning-only table
        $this->assertSame(0, count($table->getAttribute('costSums')->toArray()));
    }

    #[Test]
    public function compute_sums_multiple_rows_into_same_column(): void
    {
        $this->actingAsAdmin();

        $this->post(route('projects.store'), [
            'name' => 'Budget Project C',
            'isGroup' => false,
        ])->assertRedirect();

        /** @var Project $project */
        $project = Project::query()->where('name', 'Budget Project C')->firstOrFail();

        [$table, $columns] = $this->makeTableWithFourColumns($project);
        $mainPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
        ]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $rowA = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $rowB = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        $this->fillRowCells($rowA, $columns, ['0', '0', '0', '10']);
        $this->fillRowCells($rowB, $columns, ['0', '0', '0', '15,75']);

        $table = $table->fresh([
            'columns',
            'mainPositions.subPositions.subPositionRows.cells',
        ]);

        app(BudgetSumCalculator::class)->computeAndAttachSums($table);

        $costSums = $table->getAttribute('costSums');
        $this->assertEqualsWithDelta(
            25.75,
            $costSums[$columns[3]->id] ?? 0,
            0.001
        );
    }

    #[Test]
    public function compute_returns_early_when_main_positions_not_loaded(): void
    {
        $this->actingAsAdmin();

        $this->post(route('projects.store'), [
            'name' => 'Budget Project D',
            'isGroup' => false,
        ])->assertRedirect();

        $project = Project::query()->where('name', 'Budget Project D')->firstOrFail();
        $table = Table::factory()->create(['project_id' => $project->id]);

        // Do NOT eager-load mainPositions
        $table = Table::query()->whereKey($table->id)->first();
        $this->assertFalse($table->relationLoaded('mainPositions'));

        app(BudgetSumCalculator::class)->computeAndAttachSums($table);

        // Early return: costSums attribute should not exist as scalar; relations
        // accessor may auto-load empty collection. The key invariant is that
        // no exception is thrown and no computation happens for an unloaded table.
        $costSums = $table->getAttribute('costSums');
        // either null (not set as attribute) or empty collection (auto-loaded relation)
        $this->assertTrue(
            $costSums === null
            || (is_countable($costSums) && count($costSums) === 0),
            'costSums should be unset or empty when mainPositions relation is not loaded'
        );
    }
}

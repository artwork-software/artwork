<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetSumCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetSumCalculatorTest extends TestCase
{
    private BudgetSumCalculator $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetSumCalculator::class);
    }

    #[Test]
    public function compute_and_attach_sums_does_nothing_when_main_positions_not_loaded(): void
    {
        $table = Table::factory()->create();

        // No mainPositions relation loaded -> should early-return before
        // assigning the costSums/earningSums attributes on the model.
        $this->service->computeAndAttachSums($table);

        $this->assertFalse($table->relationLoaded('mainPositions'));
    }

    #[Test]
    public function compute_and_attach_sums_aggregates_cost_cells_after_three_columns(): void
    {
        $table = Table::factory()->create();
        $col0 = Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'commented' => false]);
        $col1 = Column::factory()->create(['table_id' => $table->id, 'position' => 1, 'commented' => false]);
        $col2 = Column::factory()->create(['table_id' => $table->id, 'position' => 2, 'commented' => false]);
        $col3 = Column::factory()->create(['table_id' => $table->id, 'position' => 3, 'commented' => false]);

        $mainPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
        ]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id, 'commented' => false]);

        ColumnCell::factory()->create([
            'column_id' => $col3->id,
            'sub_position_row_id' => $row->id,
            'value' => '100,50',
            'commented' => false,
        ]);

        $table->load([
            'columns',
            'mainPositions.subPositions.subPositionRows.cells',
        ]);

        $this->service->computeAndAttachSums($table);

        $costSums = $table->getAttribute('costSums');
        $this->assertNotNull($costSums);
        $this->assertEqualsWithDelta(100.50, (float) $costSums->get($col3->id), 0.001);
    }

    #[Test]
    public function compute_and_attach_sums_separates_earning_and_cost(): void
    {
        $table = Table::factory()->create();
        $col0 = Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'commented' => false]);
        $col1 = Column::factory()->create(['table_id' => $table->id, 'position' => 1, 'commented' => false]);
        $col2 = Column::factory()->create(['table_id' => $table->id, 'position' => 2, 'commented' => false]);
        $col3 = Column::factory()->create(['table_id' => $table->id, 'position' => 3, 'commented' => false]);

        $costMain = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
        ]);
        $costSub = SubPosition::factory()->create(['main_position_id' => $costMain->id]);
        $costRow = SubPositionRow::factory()->create(['sub_position_id' => $costSub->id, 'commented' => false]);
        ColumnCell::factory()->create([
            'column_id' => $col3->id,
            'sub_position_row_id' => $costRow->id,
            'value' => '500',
            'commented' => false,
        ]);

        $earningMain = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_EARNING',
        ]);
        $earningSub = SubPosition::factory()->create(['main_position_id' => $earningMain->id]);
        $earningRow = SubPositionRow::factory()->create(['sub_position_id' => $earningSub->id, 'commented' => false]);
        ColumnCell::factory()->create([
            'column_id' => $col3->id,
            'sub_position_row_id' => $earningRow->id,
            'value' => '1200',
            'commented' => false,
        ]);

        $table->load([
            'columns',
            'mainPositions.subPositions.subPositionRows.cells',
        ]);

        $this->service->computeAndAttachSums($table);

        $costSums = $table->getAttribute('costSums');
        $earningSums = $table->getAttribute('earningSums');

        $this->assertEqualsWithDelta(500.0, (float) $costSums->get($col3->id), 0.001);
        $this->assertEqualsWithDelta(1200.0, (float) $earningSums->get($col3->id), 0.001);
    }

    #[Test]
    public function compute_and_attach_sums_separates_commented_rows(): void
    {
        $table = Table::factory()->create();
        $col0 = Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'commented' => false]);
        $col1 = Column::factory()->create(['table_id' => $table->id, 'position' => 1, 'commented' => false]);
        $col2 = Column::factory()->create(['table_id' => $table->id, 'position' => 2, 'commented' => false]);
        $col3 = Column::factory()->create(['table_id' => $table->id, 'position' => 3, 'commented' => false]);

        $mainPosition = MainPosition::factory()->create([
            'table_id' => $table->id,
            'type' => 'BUDGET_TYPE_COST',
        ]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create([
            'sub_position_id' => $subPosition->id,
            'commented' => true,
        ]);

        ColumnCell::factory()->create([
            'column_id' => $col3->id,
            'sub_position_row_id' => $row->id,
            'value' => '300',
            'commented' => false,
        ]);

        $table->load([
            'columns',
            'mainPositions.subPositions.subPositionRows.cells',
        ]);

        $this->service->computeAndAttachSums($table);

        $costSums = $table->getAttribute('costSums');
        $commented = $table->getAttribute('commentedCostSums');

        $this->assertFalse($costSums->has($col3->id));
        $this->assertEqualsWithDelta(300.0, (float) $commented->get($col3->id), 0.001);
    }
}

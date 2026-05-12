<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnCellService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ColumnCellServiceTest extends TestCase
{
    private ColumnCellService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ColumnCellService::class);
    }

    #[Test]
    public function update_value_writes_new_value(): void
    {
        $cell = ColumnCell::factory()->create(['value' => '0,00']);

        $this->service->updateValue($cell, '123,45');

        $this->assertDatabaseHas('column_sub_position_row', [
            'id' => $cell->id,
            'value' => '123,45',
        ]);
    }

    #[Test]
    public function recalculate_automatic_columns_computes_sum(): void
    {
        $table = Table::factory()->create();
        $first = Column::factory()->create(['table_id' => $table->id, 'type' => 'empty', 'position' => 0]);
        $second = Column::factory()->create(['table_id' => $table->id, 'type' => 'empty', 'position' => 1]);
        $sum = Column::factory()->create([
            'table_id' => $table->id,
            'type' => 'sum',
            'position' => 2,
            'linked_first_column' => $first->id,
            'linked_second_column' => $second->id,
        ]);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        ColumnCell::factory()->create([
            'column_id' => $first->id,
            'sub_position_row_id' => $row->id,
            'value' => '100,00',
        ]);
        ColumnCell::factory()->create([
            'column_id' => $second->id,
            'sub_position_row_id' => $row->id,
            'value' => '50,50',
        ]);
        $sumCell = ColumnCell::factory()->create([
            'column_id' => $sum->id,
            'sub_position_row_id' => $row->id,
            'value' => '0',
        ]);

        $this->service->recalculateAutomaticColumns($row->id);

        $sumCell->refresh();
        $this->assertSame('150.50', $sumCell->value);
    }

    #[Test]
    public function recalculate_automatic_columns_computes_difference(): void
    {
        $table = Table::factory()->create();
        $first = Column::factory()->create(['table_id' => $table->id, 'type' => 'empty', 'position' => 0]);
        $second = Column::factory()->create(['table_id' => $table->id, 'type' => 'empty', 'position' => 1]);
        $diff = Column::factory()->create([
            'table_id' => $table->id,
            'type' => 'difference',
            'position' => 2,
            'linked_first_column' => $first->id,
            'linked_second_column' => $second->id,
        ]);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        ColumnCell::factory()->create([
            'column_id' => $first->id,
            'sub_position_row_id' => $row->id,
            'value' => '200',
        ]);
        ColumnCell::factory()->create([
            'column_id' => $second->id,
            'sub_position_row_id' => $row->id,
            'value' => '75,25',
        ]);
        $diffCell = ColumnCell::factory()->create([
            'column_id' => $diff->id,
            'sub_position_row_id' => $row->id,
            'value' => '0',
        ]);

        $this->service->recalculateAutomaticColumns($row->id);

        $diffCell->refresh();
        $this->assertSame('124.75', $diffCell->value);
    }
}

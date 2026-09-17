<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
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

    private function createSageBooking(ColumnCell $sageCell, string $amount, int $sageId = 4711): void
    {
        SageAssignedData::create([
            'column_cell_id' => $sageCell->id,
            'sage_id' => $sageId,
            'tan' => 1,
            'periode' => 7,
            'kto_haben' => '4400',
            'kto_soll' => '6300',
            'sa_kto' => '1',
            'kst_traeger' => 'KT-1',
            'kst_stelle' => 'KS-1',
            'kreditor' => 'Testkreditor',
            'buchungstext' => 'Testbuchung',
            'buchungsbetrag' => $amount,
            'belegnummer' => 'B-1',
            'belegdatum' => now()->toDateString(),
            'buchungsdatum' => now()->toDateString(),
        ]);
    }

    #[Test]
    public function create_cells_for_difference_column_uses_sage_bookings_of_a_sage_column(): void
    {
        $table = Table::factory()->create();
        $plan = Column::factory()->create(['table_id' => $table->id, 'type' => 'empty', 'position' => 3]);
        $sage = Column::factory()->create(['table_id' => $table->id, 'type' => 'sage', 'position' => 4]);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $rowWithBookings = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $rowWithoutSageCell = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        ColumnCell::factory()->create([
            'column_id' => $plan->id,
            'sub_position_row_id' => $rowWithBookings->id,
            'value' => '1000,00',
        ]);
        // Sage-Zellen tragen ihren Betrag NICHT in value, sondern in den Buchungen.
        $sageCell = ColumnCell::factory()->create([
            'column_id' => $sage->id,
            'sub_position_row_id' => $rowWithBookings->id,
            'value' => '0',
        ]);
        $this->createSageBooking($sageCell, '250.50', 1);
        $this->createSageBooking($sageCell, '100.00', 2);

        ColumnCell::factory()->create([
            'column_id' => $plan->id,
            'sub_position_row_id' => $rowWithoutSageCell->id,
            'value' => '300',
        ]);

        $difference = Column::factory()->create([
            'table_id' => $table->id,
            'type' => 'difference',
            'position' => 5,
            'linked_first_column' => $plan->id,
            'linked_second_column' => $sage->id,
        ]);

        $this->service->createCellsForAutomaticColumn($difference);

        $this->assertDatabaseHas('column_sub_position_row', [
            'column_id' => $difference->id,
            'sub_position_row_id' => $rowWithBookings->id,
            'value' => '649.50',
        ]);
        // Zeilen ohne Sage-Zelle bekommen trotzdem eine Differenzzelle (Sage = 0).
        $this->assertDatabaseHas('column_sub_position_row', [
            'column_id' => $difference->id,
            'sub_position_row_id' => $rowWithoutSageCell->id,
            'value' => '300.00',
        ]);
    }

    #[Test]
    public function create_cells_for_sum_column_resolves_sage_column_as_first_operand(): void
    {
        $table = Table::factory()->create();
        $sage = Column::factory()->create(['table_id' => $table->id, 'type' => 'sage', 'position' => 3]);
        $plan = Column::factory()->create(['table_id' => $table->id, 'type' => 'empty', 'position' => 4]);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        $sageCell = ColumnCell::factory()->create([
            'column_id' => $sage->id,
            'sub_position_row_id' => $row->id,
            'value' => '0',
        ]);
        $this->createSageBooking($sageCell, '-80.25');
        ColumnCell::factory()->create([
            'column_id' => $plan->id,
            'sub_position_row_id' => $row->id,
            'value' => '100',
        ]);

        $sum = Column::factory()->create([
            'table_id' => $table->id,
            'type' => 'sum',
            'position' => 5,
            'linked_first_column' => $sage->id,
            'linked_second_column' => $plan->id,
        ]);

        $this->service->createCellsForAutomaticColumn($sum);

        $this->assertDatabaseHas('column_sub_position_row', [
            'column_id' => $sum->id,
            'sub_position_row_id' => $row->id,
            'value' => '19.75',
        ]);
    }
}

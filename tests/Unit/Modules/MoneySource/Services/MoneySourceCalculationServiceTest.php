<?php

namespace Tests\Unit\Modules\MoneySource\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneySourceCalculationServiceTest extends TestCase
{
    private MoneySourceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MoneySourceCalculationService::class);
    }

    /**
     * Baut eine Tabelle mit einer SubPosition-Zeile und 5 Spalten. columnSums skippt die
     * ersten 3 Spaltengruppen (Textspalten), es bleiben also 2 Wertspalten: die erste mit
     * 100, die zweite mit 250.
     *
     * @return array{subPosition: SubPosition, mainPosition: MainPosition, linkedColumn: Column}
     */
    private function createTableWithTwoValueColumns(): array
    {
        $table = Table::factory()->create();
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);

        $columns = Column::factory()->count(5)->create(['table_id' => $table->id]);
        foreach ($columns as $index => $column) {
            ColumnCell::factory()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $row->id,
                'value' => match ($index) {
                    3 => '100,00',
                    4 => '250,00',
                    default => '0,00',
                },
            ]);
        }

        return [
            'subPosition' => $subPosition,
            'mainPosition' => $mainPosition,
            'linkedColumn' => $columns[3],
        ];
    }

    #[Test]
    public function sub_position_sum_detail_counts_only_the_linked_column(): void
    {
        ['subPosition' => $subPosition, 'linkedColumn' => $linkedColumn] = $this->createTableWithTwoValueColumns();

        $moneySource = MoneySource::factory()->create(['is_group' => false]);
        $detail = SubPositionSumDetail::factory()->create([
            'sub_position_id' => $subPosition->id,
            'column_id' => $linkedColumn->id,
        ]);
        SumMoneySource::factory()->create([
            'sourceable_type' => SubPositionSumDetail::class,
            'sourceable_id' => $detail->id,
            'money_source_id' => $moneySource->id,
            'linked_type' => 'EARNING',
        ]);

        // Nur die verknüpfte Spalte (100) darf zählen, nicht zusätzlich die zweite Wertspalte (250).
        $this->assertSame(100.0, $this->service->getPositionSumOfOneMoneySource($moneySource));
    }

    #[Test]
    public function main_position_detail_counts_only_the_linked_column(): void
    {
        ['mainPosition' => $mainPosition, 'linkedColumn' => $linkedColumn] = $this->createTableWithTwoValueColumns();

        $moneySource = MoneySource::factory()->create(['is_group' => false]);
        $detail = MainPositionDetails::factory()->create([
            'main_position_id' => $mainPosition->id,
            'column_id' => $linkedColumn->id,
        ]);
        SumMoneySource::factory()->create([
            'sourceable_type' => MainPositionDetails::class,
            'sourceable_id' => $detail->id,
            'money_source_id' => $moneySource->id,
            'linked_type' => 'COST',
        ]);

        // COST-Verknüpfung: nur die verknüpfte Spalte, mit negativem Vorzeichen.
        $this->assertSame(-100.0, $this->service->getPositionSumOfOneMoneySource($moneySource));
    }

    #[Test]
    public function get_position_sum_returns_zero_when_no_linked_entries(): void
    {
        $moneySource = MoneySource::factory()->create(['is_group' => false]);

        $sum = $this->service->getPositionSumOfOneMoneySource($moneySource);

        $this->assertSame(0.0, $sum);
    }

    #[Test]
    public function get_position_sum_returns_zero_for_empty_group(): void
    {
        $group = MoneySource::factory()->create(['is_group' => true]);

        $sum = $this->service->getPositionSumOfOneMoneySource($group);

        $this->assertSame(0.0, $sum);
    }
}

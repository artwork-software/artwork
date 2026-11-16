<?php

namespace Tests\Feature\Modules\BusinessIntelligence;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BiBookingCountTest extends FeatureTestCase
{
    /**
     * @return array{project: Project, table: Table, rows: array<int, SubPositionRow>}
     */
    private function makeProjectWithBookings(int $rowCount, int $bookingsPerRow): array
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        $sageColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 0, 'type' => 'sage']);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);

        $rows = [];
        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
            $cell = ColumnCell::factory()->create(['column_id' => $sageColumn->id, 'sub_position_row_id' => $row->id]);
            for ($bookingIndex = 0; $bookingIndex < $bookingsPerRow; $bookingIndex++) {
                SageAssignedData::create([
                    'column_cell_id' => $cell->id,
                    'sage_id' => random_int(1, PHP_INT_MAX >> 8),
                    'tan' => 1,
                    'periode' => 202606,
                    'kto_haben' => '5400',
                    'kreditor' => 'Kasse',
                    'buchungstext' => 'Ticketverkauf',
                    'buchungsbetrag' => -10,
                    'belegnummer' => 'B-1',
                    'belegdatum' => '2026-06-11',
                    'kto_soll' => '',
                    'sa_kto' => '5400',
                    'kst_traeger' => 'KT1',
                    'kst_stelle' => '201',
                    'buchungsdatum' => '2026-06-12',
                    'is_collective_booking' => false,
                ]);
            }
            $rows[] = $row;
        }

        return ['project' => $project, 'table' => $table, 'rows' => $rows];
    }

    #[Test]
    public function batched_counts_match_the_per_project_count_and_skip_deleted_rows(): void
    {
        $first = $this->makeProjectWithBookings(rowCount: 2, bookingsPerRow: 3);
        $second = $this->makeProjectWithBookings(rowCount: 1, bookingsPerRow: 2);
        $withoutBookings = $this->makeProjectWithBookings(rowCount: 1, bookingsPerRow: 0);

        // gelöschte Zeile: ihre Buchungen zählen in beiden Varianten nicht
        $first['rows'][1]->delete();

        $service = app(BiDerivedValuesService::class);
        $counts = $service->getBookingCountsByTableId([
            $first['table']->id,
            $second['table']->id,
            $withoutBookings['table']->id,
        ]);

        self::assertSame(3, $counts[$first['table']->id]);
        self::assertSame(2, $counts[$second['table']->id]);
        self::assertArrayNotHasKey($withoutBookings['table']->id, $counts);

        self::assertSame(3, $service->getBookingCount($first['project']->fresh()));
        self::assertSame(2, $service->getBookingCount($second['project']->fresh()));
        self::assertSame([], $service->getBookingCountsByTableId([]));
    }
}

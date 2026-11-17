<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/** Spalte löschen: Zell-Relationen werden vorgeladen statt je Zeile nachgeladen. */
final class ColumnDeleteQueryCountTest extends FeatureTestCase
{
    private function columnToDeleteWithRows(int $rowCount): Column
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position, 'type' => 'empty']);
        }
        $columnToDelete = Column::factory()->create(['table_id' => $table->id, 'position' => 3, 'type' => 'empty']);
        $remainingValueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'type' => 'empty']);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        foreach (range(1, $rowCount) as $ignored) {
            $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
            foreach ([$columnToDelete, $remainingValueColumn] as $column) {
                ColumnCell::factory()->create(['column_id' => $column->id, 'sub_position_row_id' => $row->id, 'value' => '10']);
            }
        }

        return $columnToDelete;
    }

    private function countDeleteQueries(Column $column): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->delete(route('project.budget.column.delete', ['column' => $column->id]))->assertRedirect();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    #[Test]
    public function deleting_a_column_soft_deletes_all_its_cells(): void
    {
        $this->actingAsAdmin();
        $column = $this->columnToDeleteWithRows(3);

        $this->countDeleteQueries($column);

        self::assertSoftDeleted('columns', ['id' => $column->id]);
        self::assertSame(0, ColumnCell::where('column_id', $column->id)->count());
        self::assertSame(3, ColumnCell::withTrashed()->where('column_id', $column->id)->count());
    }

    #[Test]
    public function lookups_per_row_are_not_repeated(): void
    {
        $this->actingAsAdmin();
        // erster Request füllt Rechte-/Einstellungs-Caches, verfälscht sonst den Vergleich
        $this->countDeleteQueries($this->columnToDeleteWithRows(1));
        $withTwoRows = $this->countDeleteQueries($this->columnToDeleteWithRows(2));
        $withSixRows = $this->countDeleteQueries($this->columnToDeleteWithRows(6));

        // gemessen: vorher 8 Abfragen je zusätzlicher Zeile, jetzt 1 (das Löschen der Zelle selbst)
        self::assertLessThanOrEqual($withTwoRows + 4 * 2, $withSixRows);
    }
}

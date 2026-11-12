<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\DTOs\MatchRelevantProjectGroupDTO;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Feature\FeatureTestCase;

/**
 * Spalte "Unterprojekte" im Gruppenbudget: ausschließlich die Summe der passenden Unterprojekt-Zeilen,
 * ohne Treffer 0. Eigenwert und Sage-Buchungen der Gruppenzeile zählen nicht (vorher Rückfall auf sie).
 */
final class ProjectGroupSubprojectsColumnTest extends FeatureTestCase
{
    #[Test]
    public function subprojects_column_shows_only_the_sum_of_matching_subproject_rows(): void
    {
        [$table, $subprojectsColumn, $matchedRow, $unmatchedRow] = $this->groupTable();

        $this->enrich($table, ['COST' => [
            $this->match($matchedRow, '30,00'),
            $this->match($matchedRow, '12,50'),
        ]]);

        $this->assertSame('42,50', $this->subprojectsCellOf($table, $matchedRow, $subprojectsColumn)->value);
        $this->assertSame('0', $this->subprojectsCellOf($table, $unmatchedRow, $subprojectsColumn)->value);
    }

    #[Test]
    public function without_matching_subproject_rows_the_own_value_and_sage_bookings_of_the_group_row_are_ignored(): void
    {
        [$table, $subprojectsColumn, $matchedRow] = $this->groupTable();

        $this->enrich($table, []);

        $this->assertSame('0', $this->subprojectsCellOf($table, $matchedRow, $subprojectsColumn)->value);
    }

    /**
     * Gruppenzeilen mit eigenem Wert in der budgetrelevanten Spalte und einer Sage-Buchung.
     *
     * @return array{0: Table, 1: Column, 2: SubPositionRow, 3: SubPositionRow}
     */
    private function groupTable(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        $relevantColumn = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);
        $sageColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 4, 'type' => 'sage']);
        $subprojectsColumn = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 100,
            'type' => 'subprojects_column_for_group',
        ]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);

        $rows = [];
        foreach ([1, 2] as $order) {
            $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id, 'order' => $order]);
            ColumnCell::factory()->create([
                'column_id' => $relevantColumn->id,
                'sub_position_row_id' => $row->id,
                'value' => '100,00',
            ]);
            $sageCell = ColumnCell::factory()->create([
                'column_id' => $sageColumn->id,
                'sub_position_row_id' => $row->id,
            ]);
            SageAssignedData::factory()->create(['column_cell_id' => $sageCell->id, 'buchungsbetrag' => 55.5]);
            ColumnCell::factory()->create([
                'column_id' => $subprojectsColumn->id,
                'sub_position_row_id' => $row->id,
                'value' => '',
            ]);
            $rows[] = $row;
        }

        $table->load(['columns', 'mainPositions.subPositions.subPositionRows.cells.sageAssignedData']);

        return [$table, $subprojectsColumn, $rows[0], $rows[1]];
    }

    /**
     * @param array<string, array<int, MatchRelevantProjectGroupDTO>> $groupedProjectData
     */
    private function enrich(Table $table, array $groupedProjectData): void
    {
        $method = new ReflectionMethod(BudgetService::class, 'enrichProjectGroupSubprojectsColumnValues');
        $method->invoke(app(BudgetService::class), $table, $groupedProjectData);
    }

    private function match(SubPositionRow $groupRow, string $value): MatchRelevantProjectGroupDTO
    {
        return new MatchRelevantProjectGroupDTO(
            subProjectId: 1,
            subProjectName: 'Unterprojekt',
            groupRowId: $groupRow->id,
            groupRowName: null,
            subPositionId: $groupRow->sub_position_id,
            mainPositionId: null,
            relevantColumnId: 999999,
            relevantColumnName: null,
            value: $value,
            cellId: null,
            type: 'COST',
            commented: false,
            value1: '',
            value2: '',
        );
    }

    private function subprojectsCellOf(Table $table, SubPositionRow $row, Column $subprojectsColumn): ColumnCell
    {
        return $table->mainPositions->first()->subPositions->first()->subPositionRows
            ->firstWhere('id', $row->id)
            ->cells
            ->firstWhere('column_id', $subprojectsColumn->id);
    }
}

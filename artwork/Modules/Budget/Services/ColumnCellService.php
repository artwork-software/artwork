<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Repositories\ColumnCellRepository;
use Illuminate\Database\Eloquent\Builder;

readonly class ColumnCellService
{
    public function __construct(private ColumnCellRepository $columnCellRepository)
    {
    }

    public function forceDelete(
        ColumnCell $columnCell,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService,
    ): void {
        $columnCell->comments->each(function (CellComment $cellComment) use ($cellCommentService): void {
            $cellCommentService->forceDelete($cellComment);
        });

        $columnCell->calculations->each(
            function (CellCalculation $cellCalculation) use ($cellCalculationService): void {
                $cellCalculationService->forceDelete($cellCalculation);
            }
        );

        if (!$columnCell->subPositionRow->subPosition->mainPosition->table->is_template) {
            /** @var SageAssignedData $sageAssignedData */
            foreach ($columnCell->sageAssignedData as $sageAssignedData) {
                /*
                 * check if other SageAssignedData entities exist by sage_id, except the given one
                 * if multiple are found we iterate through and forceDelete them, right after a global SageAssignedData
                 * entity was created - it means "sage_id" was also assigned to one or more project group(s)
                 * if not given SageAssignedData is moved to SageNotAssignedData as project related
                 */

                $assignedSageDataBySageIdExcluded = $sageAssignedDataService->findAllBySageIdExcluded(
                    $sageAssignedData->getAttribute('sage_id'),
                    [$sageAssignedData->getAttribute('id')]
                );

                if ($assignedSageDataBySageIdExcluded->count() > 0) {
                    $sageNotAssignedDataService->createFromSageAssignedData($sageAssignedData);
                    $sageAssignedDataService->forceDelete($sageAssignedData);

                    foreach ($assignedSageDataBySageIdExcluded as $assignedSageData) {
                        $sageAssignedDataService->forceDelete($assignedSageData);
                    }
                    continue;
                }

                $sageNotAssignedDataService->createFromSageAssignedData(
                    $sageAssignedData,
                    $columnCell->subPositionRow->subPosition->mainPosition->table->project_id
                );
                $sageAssignedDataService->forceDelete($sageAssignedData);
            }
        }

        $this->columnCellRepository->forceDelete($columnCell);
    }

    public function resetCellCalculationsPosition(
        ColumnCell $columnCell,
        CellCalculationService $cellCalculationService,
    ): void {
        $columnCell->calculations->each(function ($cellCalculation, $index) use ($cellCalculationService): void {
            $cellCalculationService->update($cellCalculation, ['position' => $index]);
        });
    }

    public function updateValue(ColumnCell $columnCell, mixed $value): void
    {
        $this->columnCellRepository->update($columnCell, ['value' => $value]);
    }

    /**
     * Legt die Zellen einer neuen Summen-/Differenzspalte fuer alle Zeilen der
     * Tabelle an und berechnet dabei den Startwert aus den beiden verknuepften
     * Spalten. Sage-Spalten liefern ihren Wert nicht ueber `value`, sondern
     * ueber die zugeordneten Buchungen (sage_value) - deshalb laeuft die
     * Aufloesung hier ueber dieselbe Logik wie die spaetere Neuberechnung.
     */
    public function createCellsForAutomaticColumn(Column $column): void
    {
        $table = $column->table;
        $subPositionRows = SubPositionRow::query()
            ->whereHas(
                'subPosition.mainPosition',
                fn (Builder $query) => $query->where('table_id', $table->id)
            )
            ->get();

        if ($subPositionRows->isEmpty()) {
            return;
        }

        $linkedColumns = Column::query()
            ->whereIn('id', array_filter([$column->linked_first_column, $column->linked_second_column]))
            ->get()
            ->keyBy('id');

        $linkedCells = ColumnCell::query()
            ->whereIn('column_id', $linkedColumns->keys())
            ->whereIn('sub_position_row_id', $subPositionRows->pluck('id'))
            ->with('sageAssignedData')
            ->get()
            ->groupBy('sub_position_row_id');

        foreach ($subPositionRows as $subPositionRow) {
            $rowCells = $linkedCells->get($subPositionRow->id, collect())->keyBy('column_id');

            $firstCell = $rowCells->get($column->linked_first_column);
            $secondCell = $rowCells->get($column->linked_second_column);

            ColumnCell::create([
                'column_id' => $column->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => $this->calculateAutomaticValue(
                    $column,
                    $this->resolveLinkedValue($linkedColumns->get($column->linked_first_column), $firstCell),
                    $this->resolveLinkedValue($linkedColumns->get($column->linked_second_column), $secondCell)
                ),
                'verified_value' => null,
                'linked_money_source_id' => null,
                'commented' => (bool) ($secondCell?->commented ?? $firstCell?->commented ?? false),
            ]);
        }
    }

    public function recalculateAutomaticColumns(int $subPositionRowId): void
    {
        // Zellen + Spalten der Zeile einmal laden statt pro automatischer
        // Spalte einzeln (frueher: 4+ Queries pro Spalte, jede Speicherung
        // feuerte zudem den Cache-Invalidierungs-Observer).
        $cells = ColumnCell::where('sub_position_row_id', $subPositionRowId)->get()->keyBy('column_id');
        $columns = Column::whereIn('id', $cells->keys())->get()->keyBy('id');

        foreach ($cells as $cell) {
            $column = $columns->get($cell->column_id);

            if (!$column || $column->type === 'empty' || $column->type === 'sage') {
                continue;
            }

            $result = $this->calculateAutomaticValue(
                $column,
                $this->resolveLinkedValue(
                    $columns->get($column->linked_first_column),
                    $cells->get($column->linked_first_column)
                ),
                $this->resolveLinkedValue(
                    $columns->get($column->linked_second_column),
                    $cells->get($column->linked_second_column)
                )
            );

            // Nur bei tatsaechlicher Aenderung speichern - save() feuert sonst
            // trotzdem den saved-Observer und damit die Cache-Invalidierung.
            if ($cell->value !== $result) {
                $cell->update(['value' => $result]);
            }
        }
    }

    /**
     * Wert einer verknuepften Zelle: Sage-Spalten tragen ihren Betrag in den
     * zugeordneten Buchungen (sage_value), alle anderen Spalten in `value`.
     */
    private function resolveLinkedValue(?Column $linkedColumn, ?ColumnCell $linkedCell): ?string
    {
        if ($linkedCell === null) {
            return null;
        }

        return $linkedColumn?->type === 'sage'
            ? $linkedCell->sage_value
            : $linkedCell->value;
    }

    private function calculateAutomaticValue(Column $column, ?string $firstRowValue, ?string $secondRowValue): string
    {
        $firstDecimal = str_replace(',', '.', $firstRowValue ?: '0');
        $secondDecimal = str_replace(',', '.', $secondRowValue ?: '0');

        return $column->type === 'sum'
            ? bcadd($firstDecimal, $secondDecimal, 2)
            : bcsub($firstDecimal, $secondDecimal, 2);
    }

    public function softDelete(
        ColumnCell $columnCell,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService,
    ): void {
        $columnCell->comments->each(function (CellComment $cellComment) use ($cellCommentService): void {
            $cellCommentService->delete($cellComment);
        });

        $columnCell->calculations->each(
            function (CellCalculation $cellCalculation) use ($cellCalculationService): void {
                $cellCalculationService->delete($cellCalculation);
            }
        );

        if (!$columnCell->subPositionRow->subPosition->mainPosition->table->is_template) {
            /** @var SageAssignedData $sageAssignedData */
            foreach ($columnCell->sageAssignedData as $sageAssignedData) {
                /*
                 * check if other SageAssignedData entities exist by sage_id, except the given one
                 * if multiple are found we iterate through and forceDelete them, right after a global SageAssignedData
                 * entity was created - it means "sage_id" was also assigned to one or more project group(s)
                 * if not given SageAssignedData is moved to SageNotAssignedData as project related
                 */

                $assignedSageDataBySageIdExcluded = $sageAssignedDataService->findAllBySageIdExcluded(
                    $sageAssignedData->getAttribute('sage_id'),
                    [$sageAssignedData->getAttribute('id')]
                );

                if ($assignedSageDataBySageIdExcluded->count() > 0) {
                    $sageNotAssignedDataService->createFromSageAssignedData($sageAssignedData);
                    $sageAssignedDataService->delete($sageAssignedData);

                    foreach ($assignedSageDataBySageIdExcluded as $assignedSageData) {
                        $sageAssignedDataService->delete($assignedSageData);
                    }
                    continue;
                }

                $sageNotAssignedDataService->createFromSageAssignedData(
                    $sageAssignedData,
                    $columnCell->subPositionRow->subPosition->mainPosition->table->project_id
                );
                $sageAssignedDataService->delete($sageAssignedData);
            }
        }

        $this->columnCellRepository->delete($columnCell);
    }

    public function restore(
        ColumnCell $columnCell,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService,
    ): void {
        $columnCell->comments()->withTrashed()->get()->each(
            fn (CellComment $cellComment) => $cellCommentService->restore($cellComment)
        );

        $columnCell->calculations()->withTrashed()->get()->each(
            fn (CellCalculation $cellCalculation) => $cellCalculationService->restore($cellCalculation)
        );

        // Table sauber (inkl. softdeleted) holen, statt über Property-Kette
        $table = $columnCell->subPositionRow()
            ->withTrashed()
            ->with([
                'subPosition' => fn ($q) => $q->withTrashed()->with([
                    'mainPosition' => fn ($q) => $q->withTrashed()->with([
                        'table' => fn ($q) => $q->withTrashed(),
                    ]),
                ]),
            ])
            ->first()
            ?->subPosition
            ?->mainPosition
            ?->table;

        // SageAssignedData kann ebenfalls softdeleted sein -> withTrashed()
        if ($table && !$table->is_template) {
            foreach ($columnCell->sageAssignedData()->withTrashed()->get() as $sageAssignedData) {
                $excluded = [$sageAssignedData->getAttribute('id')];

                $assignedSageDataBySageIdExcluded = $sageAssignedDataService->findAllBySageIdExcluded(
                    $sageAssignedData->getAttribute('sage_id'),
                    $excluded
                );

                if ($assignedSageDataBySageIdExcluded->count() > 0) {
                    $sageNotAssignedDataService->createFromSageAssignedData($sageAssignedData);
                    $sageAssignedDataService->restore($sageAssignedData);

                    foreach ($assignedSageDataBySageIdExcluded as $assignedSageData) {
                        $sageAssignedDataService->restore($assignedSageData);
                    }
                    continue;
                }

                $sageNotAssignedDataService->createFromSageAssignedData(
                    $sageAssignedData,
                    $table->project_id
                );
                $sageAssignedDataService->restore($sageAssignedData);
            }
        }

        $this->columnCellRepository->restore($columnCell);
    }
}

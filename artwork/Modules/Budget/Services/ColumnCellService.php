<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Repositories\ColumnCellRepository;

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

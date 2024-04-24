<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Services\CellCalculationService;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class CellCalculationsController extends Controller
{
    public function __construct(
        private readonly CellCalculationService $cellCalculationsService,
        private readonly ColumnCellService $columnCellService
    ) {
    }

    public function destroy(
        CellCalculation $cellCalculation,
        CellCalculationService $cellCalculationService
    ): RedirectResponse {
        $columnCell = $cellCalculation->cell;

        $this->cellCalculationsService->delete($cellCalculation);

        $this->columnCellService->resetCellCalculationsPosition($columnCell, $cellCalculationService);

        return Redirect::back();
    }
}

<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Repositories\CellCalculationRepository;

class CellCalculationService
{
    public function __construct(
        private readonly CellCalculationRepository $cellCalculationsRepository
    ) {
    }

    public function forceDelete(CellCalculation $cellCalculation): void
    {
        $this->cellCalculationsRepository->forceDelete($cellCalculation);
    }

    public function update(CellCalculation $cellCalculation, array $attributes): void
    {
        $this->cellCalculationsRepository->update($cellCalculation, $attributes);
    }
}

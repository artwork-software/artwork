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

    public function delete(CellCalculation $cellCalculation): void
    {
        $this->cellCalculationsRepository->delete($cellCalculation);
    }

    public function update(CellCalculation $cellCalculation, array $attributes): void
    {
        $this->cellCalculationsRepository->update($cellCalculation, $attributes);
    }
}

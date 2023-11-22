<?php

namespace Artwork\Modules\Budget\Services;

use App\Enums\BudgetTypesEnum;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\MainPositionRepository;

class MainPositionService
{
    public function __construct(
        private readonly MainPositionRepository $mainPositionRepository,
    ){}

    public function createMainPosition(Table $table, BudgetTypesEnum $budgetTypesEnum, string $name, int $position): MainPosition|Model
    {
        $mainPosition = new MainPosition();
        $mainPosition->table_id = $table->id;
        $mainPosition->type = $budgetTypesEnum->value;
        $mainPosition->name = $name;
        $mainPosition->position = $position;
        return $this->mainPositionRepository->save($mainPosition);
    }
}

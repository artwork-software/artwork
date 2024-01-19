<?php

namespace Artwork\Modules\Vacation\Services;

use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Repository\VacationConflictRepository;

class VacationConflictService
{
    public function __construct(
        private readonly VacationConflictRepository $vacationConflictRepository,
    ) {
    }


    public function create(array $data): void
    {
        $conflict = new VacationConflict();
        $conflict->fill($data);
        $this->vacationConflictRepository->save($conflict);
    }
}

<?php

namespace Artwork\Modules\Contract\Services;

use Artwork\Modules\Contract\Repositories\ContractTypeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class ContractTypeService
{
    public function __construct(private ContractTypeRepository $contractTypeRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->contractTypeRepository->getAll();
    }
}

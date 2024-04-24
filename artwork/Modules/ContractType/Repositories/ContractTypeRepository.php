<?php

namespace Artwork\Modules\ContractType\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ContractType\Models\ContractType;
use Illuminate\Database\Eloquent\Collection;

readonly class ContractTypeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return ContractType::all();
    }
}

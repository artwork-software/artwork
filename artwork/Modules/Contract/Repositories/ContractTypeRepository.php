<?php

namespace Artwork\Modules\Contract\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Contract\Models\ContractType;
use Illuminate\Database\Eloquent\Collection;

class ContractTypeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return ContractType::all();
    }
}

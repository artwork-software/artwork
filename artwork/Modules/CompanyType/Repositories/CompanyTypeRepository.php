<?php

namespace Artwork\Modules\CompanyType\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Illuminate\Database\Eloquent\Collection;

readonly class CompanyTypeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return CompanyType::all();
    }
}

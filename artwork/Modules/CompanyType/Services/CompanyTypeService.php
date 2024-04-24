<?php

namespace Artwork\Modules\CompanyType\Services;

use Artwork\Modules\CompanyType\Repositories\CompanyTypeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CompanyTypeService
{
    public function __construct(private CompanyTypeRepository $companyTypeRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->companyTypeRepository->getAll();
    }
}

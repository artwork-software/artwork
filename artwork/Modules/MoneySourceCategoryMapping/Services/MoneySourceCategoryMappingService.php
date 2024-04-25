<?php

namespace Artwork\Modules\MoneySourceCategoryMapping\Services;

use Artwork\Modules\MoneySourceCategoryMapping\Repositories\MoneySourceCategoryMappingRepository;

readonly class MoneySourceCategoryMappingService
{
    public function __construct(private MoneySourceCategoryMappingRepository $moneySourceCategoryMappingRepository)
    {
    }
}

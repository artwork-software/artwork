<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Repositories\MoneySourceCategoryMappingRepository;

readonly class MoneySourceCategoryMappingService
{
    public function __construct(private MoneySourceCategoryMappingRepository $moneySourceCategoryMappingRepository)
    {
    }
}

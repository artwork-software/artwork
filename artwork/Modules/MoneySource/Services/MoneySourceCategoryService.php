<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Repositories\MoneySourceCategoryRepository;

readonly class MoneySourceCategoryService
{
    public function __construct(private MoneySourceCategoryRepository $moneySourceCategoryRepository)
    {
    }
}

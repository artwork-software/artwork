<?php

namespace Artwork\Modules\MoneySourceCategory\Services;

use Artwork\Modules\MoneySourceCategory\Repositories\MoneySourceCategoryRepository;

readonly class MoneySourceCategoryService
{
    public function __construct(private MoneySourceCategoryRepository $moneySourceCategoryRepository)
    {
    }
}

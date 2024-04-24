<?php

namespace Artwork\Modules\MoneySourceUserPivot\Services;

use Artwork\Modules\MoneySourceUserPivot\Repositories\MoneySourceUserPivotRepository;

readonly class MoneySourceUserPivotService
{
    public function __construct(private MoneySourceUserPivotRepository $moneySourceUserPivotRepository)
    {
    }
}

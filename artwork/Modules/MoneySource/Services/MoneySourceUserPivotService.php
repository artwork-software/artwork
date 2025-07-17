<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Repositories\MoneySourceUserPivotRepository;

readonly class MoneySourceUserPivotService
{
    public function __construct(private MoneySourceUserPivotRepository $moneySourceUserPivotRepository)
    {
    }
}

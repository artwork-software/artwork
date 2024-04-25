<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Repositories\MoneySourceRepository;

class MoneySourceService
{
    public function __construct(private MoneySourceRepository $moneySourceRepository)
    {
    }
}

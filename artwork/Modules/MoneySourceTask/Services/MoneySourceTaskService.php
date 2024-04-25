<?php

namespace Artwork\Modules\MoneySourceTask\Services;

use Artwork\Modules\MoneySourceTask\Repositories\MoneySourceTaskRepository;

readonly class MoneySourceTaskService
{
    public function __construct(private MoneySourceTaskRepository $moneySourceTaskRepository)
    {
    }
}

<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Repositories\MoneySourceRepository;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Illuminate\Database\Eloquent\Collection;

class MoneySourceService
{
    public function __construct(private MoneySourceRepository $moneySourceRepository)
    {
    }
}

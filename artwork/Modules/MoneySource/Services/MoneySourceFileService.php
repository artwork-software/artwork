<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Repositories\MoneySourceFileRepository;

readonly class MoneySourceFileService
{
    public function __construct(private MoneySourceFileRepository $moneySourceFileRepository)
    {
    }
}

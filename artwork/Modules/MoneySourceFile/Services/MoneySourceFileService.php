<?php

namespace Artwork\Modules\MoneySourceFile\Services;

use Artwork\Modules\MoneySourceFile\Repositories\MoneySourceFileRepository;

readonly class MoneySourceFileService
{
    public function __construct(private MoneySourceFileRepository $moneySourceFileRepository)
    {
    }
}

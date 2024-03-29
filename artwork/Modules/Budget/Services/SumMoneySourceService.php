<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\SumMoneySourceRepository;

class SumMoneySourceService
{
    public function __construct(
        private readonly SumMoneySourceRepository $sumMoneySourceRepository
    ) {
    }

    public function forceDelete(SumMoneySource $sumMoneySource): void
    {
        $this->sumMoneySourceRepository->forceDelete($sumMoneySource);
    }
}

<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\SumMoneySourceRepository;

readonly class SumMoneySourceService
{
    public function __construct(private SumMoneySourceRepository $sumMoneySourceRepository)
    {
    }

    public function forceDelete(SumMoneySource $sumMoneySource): void
    {
        $this->sumMoneySourceRepository->forceDelete($sumMoneySource);
    }

    // softDelete
    public function softDelete(SumMoneySource $sumMoneySource): void
    {
        $this->sumMoneySourceRepository->delete($sumMoneySource);
    }

    //restore
    public function restore(SumMoneySource $sumMoneySource): void
    {
        $this->sumMoneySourceRepository->restore($sumMoneySource);
    }
}

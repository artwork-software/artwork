<?php

namespace Artwork\Modules\CostCenter\Services;

use Artwork\Modules\CostCenter\Repositories\CostCenterRepository;

readonly class CostCenterService
{
    public function __construct(private CostCenterRepository $costCenterRepository)
    {
    }
}

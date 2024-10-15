<?php

namespace Artwork\Modules\CostCenter\Services;

use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\CostCenter\Repositories\CostCenterRepository;

readonly class CostCenterService
{
    public function __construct(private CostCenterRepository $costCenterRepository)
    {
    }

    public function findOrCreateCostCenter(string $name): CostCenter
    {
        $createdOrFoundedCostCenter = $this->costCenterRepository->findCostCenterByName($name);

        if ($createdOrFoundedCostCenter === null) {
            $costCenter = new CostCenter();
            $costCenter->name = $name;

            $createdOrFoundedCostCenter = $this->costCenterRepository->save($costCenter);
        }

        return $createdOrFoundedCostCenter;
    }
}

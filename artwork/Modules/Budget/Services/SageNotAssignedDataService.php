<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Repositories\SageNotAssignedDataRepository;

class SageNotAssignedDataService
{
    public function __construct(
        private readonly SageNotAssignedDataRepository $sageNotAssignedDataRepository,
    ) {
    }



    public function store(array $data): SageNotAssignedData|Model
    {
        $sage = new SageNotAssignedData();
        $sage->sage_id = $data['ID'];
        $sage->cost_center = $data['KstTraeger'];
        $sage->kst = $data['KstStelle'];
        $sage->kto = $data['SaKto'];
        $sage->description = $data['Buchungstext'];
        $sage->amount = $data['Buchungsbetrag'];
        return $this->sageNotAssignedDataRepository->save($sage);
    }
}

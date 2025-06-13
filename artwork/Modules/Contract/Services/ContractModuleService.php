<?php

namespace Artwork\Modules\Contract\Services;

use Artwork\Modules\Contract\Repositories\ContractModuleRepository;

readonly class ContractModuleService
{
    public function __construct(private ContractModuleRepository $contractModuleRepository)
    {
    }
}

<?php

namespace Artwork\Modules\ContractModule\Services;

use Artwork\Modules\ContractModule\Repositories\ContractModuleRepository;

readonly class ContractModuleService
{
    public function __construct(private ContractModuleRepository $contractModuleRepository)
    {
    }
}

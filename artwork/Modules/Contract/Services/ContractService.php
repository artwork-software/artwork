<?php

namespace Artwork\Modules\Contract\Services;

use Artwork\Modules\Contract\Repositories\ContractRepository;

readonly class ContractService
{
    public function __construct(private ContractRepository $contractRepository)
    {
    }
}

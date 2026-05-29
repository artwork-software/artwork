<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessRepository;

class ExternalAccessService
{
    public function __construct(
        private readonly ExternalAccessRepository $externalAccessRepository,
    ) {
    }

    public function findByEmail(string $email): ?ExternalAccess
    {
        return $this->externalAccessRepository->findByEmail($email);
    }
}

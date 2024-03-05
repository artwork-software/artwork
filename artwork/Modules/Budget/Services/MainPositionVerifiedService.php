<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\MainPositionVerified;
use Artwork\Modules\Budget\Repositories\MainPositionVerifiedRepository;

class MainPositionVerifiedService
{
    public function __construct(
        private readonly MainPositionVerifiedRepository $mainPositionVerifiedRepository
    ) {
    }

    public function forceDelete(MainPositionVerified $mainPositionVerified): void
    {
        $this->mainPositionVerifiedRepository->forceDelete($mainPositionVerified);
    }
}

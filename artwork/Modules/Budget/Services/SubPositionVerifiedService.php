<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPositionVerified;
use Artwork\Modules\Budget\Repositories\SubPositionVerifiedRepository;

class SubPositionVerifiedService
{
    public function __construct(
        private readonly SubPositionVerifiedRepository $subPositionVerifiedRepository
    ) {
    }

    public function delete(SubPositionVerified $subPositionVerified): void
    {
        $this->subPositionVerifiedRepository->delete($subPositionVerified);
    }
}

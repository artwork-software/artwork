<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPositionVerified;
use Artwork\Modules\Budget\Repositories\SubPositionVerifiedRepository;

readonly class SubPositionVerifiedService
{
    public function __construct(private SubPositionVerifiedRepository $subPositionVerifiedRepository)
    {
    }

    public function forceDelete(SubPositionVerified $subPositionVerified): void
    {
        $this->subPositionVerifiedRepository->forceDelete($subPositionVerified);
    }

    public function softDelete(SubPositionVerified $subPositionVerified): void
    {
        $this->subPositionVerifiedRepository->delete($subPositionVerified);
    }

    // restore
    public function restore(SubPositionVerified $subPositionVerified): void
    {
        $this->subPositionVerifiedRepository->restore($subPositionVerified);
    }
}

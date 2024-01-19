<?php

namespace Artwork\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Repositories\AvailabilityConflictRepository;

class AvailabilityConflictService
{
    public function __construct(
        private readonly AvailabilityConflictRepository $availabilityConflictRepository,
    ) {
    }


    public function create(array $data): void
    {
        $conflict = new AvailabilitiesConflict();
        $conflict->fill($data);
        $this->availabilityConflictRepository->save($conflict);
    }
}

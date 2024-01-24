<?php

namespace Artwork\Modules\ShiftQualification\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Collection;

class ShiftQualificationRepository extends BaseRepository
{
    public function getAllOrderedByCreationDateAscending(): Collection
    {
        return ShiftQualification::query()
            ->orderByCreationDateAscending()
            ->get();
    }

    public function getAllAvailableOrderedByCreationDateAscending(): Collection
    {
        return ShiftQualification::query()
            ->available()
            ->orderByCreationDateAscending()
            ->get();
    }
}
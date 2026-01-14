<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Collection;

class ShiftQualificationRepository extends BaseRepository
{
    public function findById(int $id): ShiftQualification|null
    {
        return ShiftQualification::find($id);
    }

    public function getAllOrderedByCreationDateAscending(): Collection
    {
        return ShiftQualification::query()
            ->select(['id', 'name', 'icon', 'available', 'created_at'])
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

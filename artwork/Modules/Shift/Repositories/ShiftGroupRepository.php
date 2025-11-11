<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Modules\Shift\Models\ShiftGroup;

class ShiftGroupRepository
{


    public function all()
    {
        return ShiftGroup::all();
    }

    public function findById(int $id): ?ShiftGroup
    {
        return ShiftGroup::find($id);
    }

    public function create(array $data): ShiftGroup
    {
        return ShiftGroup::create($data);
    }

    public function update(ShiftGroup $shiftGroup, array $data): void
    {
        $shiftGroup->update($data);
    }

    public function delete(ShiftGroup $shiftGroup): void
    {
        $shiftGroup->delete();
    }
}

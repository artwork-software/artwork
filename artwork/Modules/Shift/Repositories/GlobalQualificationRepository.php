<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Modules\Shift\Models\GlobalQualification;

class GlobalQualificationRepository
{

    public function create(array $data): void
    {
        GlobalQualification::create($data);
    }

    public function update(GlobalQualification $globalQualification, array $data): void
    {
        $globalQualification->update($data);
    }

    public function getById(int $id): ?GlobalQualification
    {
        return GlobalQualification::find($id);
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return GlobalQualification::all();
    }

    public function destroy(GlobalQualification $globalQualification): void
    {
        $globalQualification->delete();
    }
}

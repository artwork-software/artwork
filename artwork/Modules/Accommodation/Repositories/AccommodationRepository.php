<?php

namespace Artwork\Modules\Accommodation\Repositories;

use Artwork\Modules\Accommodation\Models\Accommodation;

class AccommodationRepository
{
    public function create(array $data): Accommodation
    {
        return Accommodation::create($data);
    }

    public function update(Accommodation $accommodation, array $data): Accommodation
    {
        $accommodation->update($data);
        return $accommodation;
    }

    public function destroy(Accommodation $accommodation): bool
    {
        return $accommodation->delete();
    }
}
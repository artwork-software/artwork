<?php

namespace Artwork\Modules\Manufacturer\Repositories;

use Artwork\Modules\Manufacturer\Models\Manufacturer;

class ManufacturerRepository
{
    public function allPaginated($perPage = 10)
    {
        return Manufacturer::paginate($perPage);
    }

    public function searchPaginated($search, $perPage = 10)
    {
        $ids = Manufacturer::search($search)->get()->pluck('id');
        return Manufacturer::whereIn('id', $ids)->paginate($perPage);
    }

    public function create(array $data)
    {
        return Manufacturer::create($data);
    }

    public function update(Manufacturer $manufacturer, array $data)
    {
        return $manufacturer->update($data);
    }

    public function delete(Manufacturer $manufacturer)
    {
        return $manufacturer->delete();
    }
}
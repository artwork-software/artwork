<?php

namespace Artwork\Modules\Manufacturer\Services;

use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Manufacturer\Repositories\ManufacturerRepository;

class ManufacturerService
{
    public function __construct(protected ManufacturerRepository $repository) {}

    public function getAll($search = null, $perPage = 10)
    {
        return $search
            ? $this->repository->searchPaginated($search, $perPage)
            : $this->repository->allPaginated($perPage);
    }

    public function store(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(Manufacturer $manufacturer, array $data)
    {
        return $this->repository->update($manufacturer, $data);
    }

    public function delete(Manufacturer $manufacturer)
    {
        return $this->repository->delete($manufacturer);
    }
}
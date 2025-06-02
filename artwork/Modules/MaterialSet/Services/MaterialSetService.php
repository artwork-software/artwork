<?php

namespace Artwork\Modules\MaterialSet\Services;

use Artwork\Modules\MaterialSet\Http\Requests\StoreMaterialSetRequest;
use Artwork\Modules\MaterialSet\Http\Requests\UpdateMaterialSetRequest;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\MaterialSet\Repositories\MaterialSetRepository;

class MaterialSetService
{
    public function __construct(protected MaterialSetRepository $repository) {}

    public function store(StoreMaterialSetRequest $request): MaterialSet
    {
        return $this->repository->create($request->validated());
    }

    public function update(MaterialSet $set, UpdateMaterialSetRequest $request): MaterialSet
    {
        return $this->repository->update($set, $request->validated());
    }

    public function delete(MaterialSet $set): void
    {
        $this->repository->delete($set);
    }
}

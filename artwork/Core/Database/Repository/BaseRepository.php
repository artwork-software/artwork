<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\Model;
use Throwable;

abstract class BaseRepository
{
    public function save(Model $model): Model
    {
        $model->save();
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function saveOrFail(Model $model): Model
    {
        $model->saveOrFail();
        return $model;
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * @throws Throwable
     */
    public function deleteOrFail(Model $model): bool
    {
        return $model->deleteOrFail();
    }

    public function deleteByReference(Model $model, string $referenceName): void
    {
        $model->${$referenceName}()->delete();
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(Model $model, array $attributes): Model
    {
        $model->updateOrFail($attributes);
        return $model;
    }
}

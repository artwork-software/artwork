<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

abstract class BaseRepository
{
    public function save(Model|Pivot $model): Model|Pivot
    {
        $model->save();
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function saveOrFail(Model|Pivot $model): Model|Pivot
    {
        $model->saveOrFail();
        return $model;
    }

    public function delete(Model|Pivot $model): bool
    {
        return $model->delete();
    }

    /**
     * @throws Throwable
     */
    public function deleteOrFail(Model|Pivot $model): bool
    {
        return $model->deleteOrFail();
    }

    public function deleteByReference(Model|Pivot $model, string $referenceName): void
    {
        $model->${$referenceName}()->delete();
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(Model|Pivot $model, array $attributes): Model|Pivot
    {
        $model->updateOrFail($attributes);
        return $model;
    }
}

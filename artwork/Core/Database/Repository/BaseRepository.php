<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
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

    public function forceDelete(Model|Pivot $model): bool
    {
        return $model->forceDelete();
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

    public function update(Model|Pivot $model, array $attributes): Model|Pivot
    {
        $model->update($attributes);
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(Model|Pivot $model, array $attributes): Model|Pivot
    {
        $model->updateOrFail($attributes);
        return $model;
    }

    public function restore(Model|Pivot $model): bool
    {
        if (!in_array(SoftDeletes::class, class_uses($model))) {
            throw new InvalidArgumentException(
                'Class is not using SoftDeletes-trait, therefore ' . __FUNCTION__ . ' can not be executed'
            );
        }

        return $model->restore();
    }

    public function restoreQuietly(Model|Pivot $model): bool
    {
        if (!in_array(SoftDeletes::class, class_uses($model))) {
            throw new InvalidArgumentException(
                'Class is not using SoftDeletes-trait, therefore ' . __FUNCTION__ . ' can not be executed'
            );
        }

        return $model->restoreQuietly();
    }
}

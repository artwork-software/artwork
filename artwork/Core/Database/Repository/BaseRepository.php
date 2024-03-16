<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\InteractsWithDatabase;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Throwable;

abstract class BaseRepository
{
    public function save(Model|Pivot|InteractsWithDatabase $model): Model|Pivot|InteractsWithDatabase
    {
        $model->save();
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function saveOrFail(Model|Pivot|InteractsWithDatabase $model): Model|Pivot|InteractsWithDatabase
    {
        $model->saveOrFail();

        return $model;
    }

    public function delete(Model|Pivot|InteractsWithDatabase $model): bool
    {
        return $model->delete();
    }

    public function forceDelete(Model|Pivot|InteractsWithDatabase $model): bool
    {
        return $model->forceDelete();
    }

    /**
     * @throws Throwable
     */
    public function deleteOrFail(Model|Pivot|InteractsWithDatabase $model): bool
    {
        return $model->deleteOrFail();
    }

    public function deleteByReference(Model|Pivot|InteractsWithDatabase $model, string $referenceName): void
    {
        $model->${$referenceName}()->delete();
    }

    public function update(Model|Pivot|InteractsWithDatabase $model, array $attributes): Model|Pivot
    {
        $model->update($attributes);
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(Model|Pivot|InteractsWithDatabase $model, array $attributes): Model|Pivot
    {
        $model->updateOrFail($attributes);
        return $model;
    }

    public function restore(Model|Pivot|InteractsWithDatabase $model): bool
    {
        if (!in_array(SoftDeletes::class, class_uses($model))) {
            throw new InvalidArgumentException(
                'Class is not using SoftDeletes-trait, therefore ' . __FUNCTION__ . ' can not be executed'
            );
        }

        return $model->restore();
    }

    public function restoreQuietly(Model|Pivot|InteractsWithDatabase $model): bool
    {
        if (!in_array(SoftDeletes::class, class_uses($model))) {
            throw new InvalidArgumentException(
                'Class is not using SoftDeletes-trait, therefore ' . __FUNCTION__ . ' can not be executed'
            );
        }

        return $model->restoreQuietly();
    }
}

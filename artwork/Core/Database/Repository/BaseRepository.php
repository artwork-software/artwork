<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Throwable;

abstract class BaseRepository
{
    public function save(Model|Pivot|CanSubstituteBaseModel $model): Model|Pivot|CanSubstituteBaseModel
    {
        $model->save();
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function saveOrFail(Model|Pivot|CanSubstituteBaseModel $model): Model|Pivot|CanSubstituteBaseModel
    {
        $model->saveOrFail();

        return $model;
    }

    public function delete(Model|Pivot|CanSubstituteBaseModel $model): bool
    {
        return $model->delete();
    }

    public function forceDelete(Model|Pivot|CanSubstituteBaseModel $model): bool
    {
        return $model->forceDelete();
    }

    /**
     * @throws Throwable
     */
    public function deleteOrFail(Model|Pivot|CanSubstituteBaseModel $model): bool
    {
        return $model->deleteOrFail();
    }

    public function deleteByReference(Model|Pivot|CanSubstituteBaseModel $model, string $referenceName): void
    {
        $model->${$referenceName}()->delete();
    }

    public function update(Model|Pivot|CanSubstituteBaseModel $model, array $attributes): Model|Pivot
    {
        $model->update($attributes);
        return $model;
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(Model|Pivot|CanSubstituteBaseModel $model, array $attributes): Model|Pivot
    {
        $model->updateOrFail($attributes);
        return $model;
    }

    public function restore(Model|Pivot|CanSubstituteBaseModel $model): bool
    {
        if (!in_array(SoftDeletes::class, class_uses($model), true)) {
            throw new InvalidArgumentException(
                'Class is not using SoftDeletes-trait, therefore ' . __FUNCTION__ . ' can not be executed'
            );
        }

        //restore is available by SoftDeletes trait
        return $model->restore();
    }

    public function restoreQuietly(Model|Pivot|CanSubstituteBaseModel $model): bool
    {
        if (!in_array(SoftDeletes::class, class_uses($model), true)) {
            throw new InvalidArgumentException(
                'Class is not using SoftDeletes-trait, therefore ' . __FUNCTION__ . ' can not be executed'
            );
        }

        //restore is available by SoftDeletes trait
        return $model->restoreQuietly();
    }
}

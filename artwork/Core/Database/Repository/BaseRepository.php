<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use InvalidArgumentException;
use Throwable;

abstract class BaseRepository
{
    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        throw new BadMethodCallException(
            'Implement in derived repository. Copy already derived functions and adapt.'
        );
    }

    /**
     * base builder return type is required for some unit tests where orderBy or count has to be tested
     * which are only mixed into the eloquent builder so the mock breaks while configuring methods
     * sadly we can't use addMethods on the mock as its deprecated and will be removed in next
     * PHPUnit version
     *
     * example @see CraftInventoryCategoryRepositoryTest::testGetAllByCraftIdOrderedByOrder()
     **/
    public function getNewModelQuery(): BaseBuilder|Builder
    {
        throw new BadMethodCallException(
            'Implement in derived repository. Copy already derived functions and adapt.'
        );
    }

    public function find(int|string $id): Model|Pivot|CanSubstituteBaseModel|DatabaseNotification|null
    {
        return static::getNewModelQuery()->find($id);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string $id): Model|Pivot|CanSubstituteBaseModel
    {
        return static::getNewModelQuery()->findOrFail($id);
    }

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

    public function delete(Model|Pivot|CanSubstituteBaseModel|DatabaseNotification $model): bool
    {
        return $model->delete();
    }

    public function forceDelete(Model|Pivot|CanSubstituteBaseModel|DatabaseNotification $model): bool
    {
        return $model->forceDelete();
    }

    /**
     * @throws Throwable
     */
    public function deleteOrFail(Model|Pivot|CanSubstituteBaseModel|DatabaseNotification $model): bool
    {
        return $model->deleteOrFail();
    }

    public function deleteByReference(Model|Pivot|CanSubstituteBaseModel $model, string $referenceName): void
    {
        $model->${$referenceName}()->delete();
    }

    public function update(
        Model|Pivot|CanSubstituteBaseModel $model,
        array $attributes
    ): Model|Pivot|CanSubstituteBaseModel {
        $model->update($attributes);

        return $model;
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(
        Model|Pivot|CanSubstituteBaseModel|DatabaseNotification $model,
        array $attributes
    ): Model|Pivot|CanSubstituteBaseModel|DatabaseNotification {
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

    public function replicate(
        Model|Pivot|CanSubstituteBaseModel $model,
        array $except = []
    ): Model|Pivot|CanSubstituteBaseModel {
        return $model->replicate($except);
    }

    public function findByKey(
        string $key,
    ): Model|Pivot|CanSubstituteBaseModel|null {
        return static::getNewModelQuery()->where('key', $key)->first();
    }

    public function forceDeleteAll(): void
    {
        foreach (static::getNewModelQuery()->get() as $model) {
            $model->forceDelete();
        }
    }
}

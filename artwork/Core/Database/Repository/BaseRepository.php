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

}

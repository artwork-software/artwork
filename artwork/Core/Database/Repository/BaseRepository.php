<?php

namespace Artwork\Core\Database\Repository;

use Artwork\Core\Database\Models\Model;

abstract class BaseRepository
{
    public function save(Model $model): Model
    {
        $model->save();
        return $model;
    }
}

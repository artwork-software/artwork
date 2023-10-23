<?php

namespace App\Http\Resources\ResourceMappers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait SimpleMapping
{
    protected function map(Collection $collection): Collection|\Illuminate\Support\Collection|array
    {
        return $collection->map(fn(Model $model) => [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }
}

<?php

namespace Artwork\Modules\Tasks\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskRepository extends BaseRepository
{
    public function deleteByModel(Model $model): void
    {
        $model->tasks()->delete();
    }

    public function findByModel(Model $model): Collection
    {
        return  $model->tasks()->get();
    }

    public function syncWithoutDetach(BelongsToMany $belongsToMany, array $ids): void
    {
        $belongsToMany->syncWithoutDetaching($ids);
    }
}

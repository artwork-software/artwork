<?php

namespace Artwork\Modules\Task\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

readonly class TaskRepository extends BaseRepository
{
    public function deleteByModel(Model $model): void
    {
        $model->tasks()->delete();
    }

    public function findByModel(Model $model): Collection
    {
        return  $model->tasks()->get();
    }

    public function syncWithDetach(BelongsToMany $belongsToMany, array $ids): void
    {
        $belongsToMany->sync($ids);
    }
}

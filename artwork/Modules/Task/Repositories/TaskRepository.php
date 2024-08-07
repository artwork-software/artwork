<?php

namespace Artwork\Modules\Task\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Task\Models\Task;
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

    public function syncWithDetach(BelongsToMany $belongsToMany, array $ids): void
    {
        $belongsToMany->sync($ids);
    }

    public function findById(int $id): Model|null
    {
        return Task::find($id);
    }
}

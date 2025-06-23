<?php

namespace Artwork\Modules\MoneySource\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Illuminate\Database\Eloquent\Collection;

class MoneySourceTaskRepository extends BaseRepository
{
    public function getMyMoneySourceTasks(
        int $userId,
        int $filter,
        bool $doneTask
    ): Collection {
        return MoneySourceTask::with(['money_source_task_users', 'money_source'])
            ->whereHas('money_source_task_users', function ($q) use ($userId): void {
                $q->where('user_id', $userId);
            })
            ->where('done', $doneTask)
            ->when($filter === 2, function ($q): void {
                $q->orderBy('deadline');
            })
            ->get();
    }
}

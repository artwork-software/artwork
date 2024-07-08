<?php

namespace Artwork\Modules\MoneySourceTask\Services;

use Artwork\Modules\MoneySourceTask\Models\MoneySourceTask;
use Artwork\Modules\MoneySourceTask\Repositories\MoneySourceTaskRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class MoneySourceTaskService
{
    public function __construct(private MoneySourceTaskRepository $moneySourceTaskRepository)
    {
    }




    public function getMyMoneySourceTasks(
        int $userId,
        int $filter
    ): Collection {
        $doneTask = false;
        if ($filter === 3) {
            $doneTask = true;
        }

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

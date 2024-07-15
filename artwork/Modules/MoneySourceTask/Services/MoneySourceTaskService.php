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

        return $this->moneySourceTaskRepository->getMyMoneySourceTasks($userId, $filter, $doneTask);
    }

    public function markAsDone(MoneySourceTask $moneySourceTask): void
    {
        $moneySourceTask->update(['done' => true]);
    }
}

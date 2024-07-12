<?php

namespace Artwork\Modules\Checklist\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class ChecklistRepository extends BaseRepository
{
    public function getById(int $id): mixed
    {
        return Checklist::find($id);
    }

    public function getOrderByChecklists(Checklist $checklist): int
    {
        return $checklist->tasks()->max('order') + 1;
    }

    public function getChecklistWhereHasTaskUsersWithFilteredTasks(int $userId, bool $doneTask, int $filter): Collection
    {
        return Checklist::query()
            ->whereHas('tasks', function ($q) use ($userId): void {
                $q->whereHas('task_users', function ($q) use ($userId): void {
                    $q->where('user_id', $userId);
                });
            })
            ->with(['tasks' => function ($q) use ($filter, $doneTask, $userId): void {
                $q->where('done', $doneTask)
                    ->when($filter === 1, function ($q): void {
                        $q->orderBy('order');
                    })
                    ->when($filter === 2, function ($q): void {
                        $q->orderBy('deadline');
                    })
                    ->whereHas('task_users', function ($q) use ($userId): void {
                        $q->where('user_id', $userId);
                    });
            }])
            ->get();
    }

    public function getChecklistsForUserWithFilteredTasks(int $userId, bool $doneTask, int $filter): Collection
    {
        return Checklist::query()
            ->where('user_id', $userId)
            ->with(['tasks' => function ($q) use ($doneTask, $filter): void {
                $q->where('done', $doneTask)
                    ->when($filter === 1, function ($q): void {
                        $q->orderBy('order');
                    })
                    ->when($filter === 2, function ($q): void {
                        $q->orderBy('deadline');
                    });
            }])
            ->get();
    }
}

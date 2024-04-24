<?php

namespace Artwork\Modules\Task\Services;

use Artwork\Modules\Task\Repositories\TaskRepository;

readonly class TaskService
{
    public function __construct(private TaskRepository $taskRepository)
    {
    }
}

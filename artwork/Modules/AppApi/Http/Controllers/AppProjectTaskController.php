<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppStoreTaskRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Checklist\Events\ChecklistUpdated;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppProjectTaskController extends Controller
{
    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
        private readonly TaskService $taskService,
    ) {
    }

    public function store(AppStoreTaskRequest $request, Project $project, Checklist $checklist): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('update', $checklist);

        $task = $this->taskService->createTaskByRequest(
            $checklist,
            $request->validated('name'),
            $request->user()->id,
            null,
            $request->validated('deadline'),
            [],
        );

        broadcast(new ChecklistUpdated($project->id))->toOthers();

        return response()->json(['task' => $this->systemComponentService->mapTask($task)], 201);
    }

    public function toggleDone(Request $request, Project $project, Task $task): JsonResponse
    {
        // Tasks hang off checklists, so the route cannot scope them through the project.
        $checklist = $task->checklist;
        abort_unless($checklist?->project_id === $project->id, 404);
        $this->authorize('view', $project);
        $this->authorize('update', $checklist);

        $task = $this->taskService->doneOrUndoneTask($task, $request->user()->id);

        broadcast(new ChecklistUpdated($project->id))->toOthers();

        return response()->json(['task' => $this->systemComponentService->mapTask($task)]);
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiTaskData;
use Artwork\Modules\AiAssistant\DTOs\AiTaskListData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteItemData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteSummaryData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\AiAssistant\Support\WebGuardScope;
use Artwork\Modules\AppApi\Services\AppDashboardService;
use Artwork\Modules\Checklist\Events\ChecklistUpdated;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eigene Aufgaben lesen und abhaken (ai-scenarios-3 S5).
 *
 * Menge wie das Dashboard (`AppDashboardService::ownTasksQuery()`: eigene Checkliste oder
 * zugewiesen), zusaetzlich gefiltert ueber `ChecklistPolicy::view` (Tab-Sichtbarkeit) im Guard
 * `web`. Abhaken laeuft ueber `TaskService::doneOrUndoneTask()` wie
 * `TaskController::updateDoneStatus()` (inkl. `ChecklistUpdated`-Broadcast, hier ueber
 * `SafeBroadcast`), mit Batch fuer Undo (Vorzustand `done`/`done_at`/`user_id`).
 */
class AiTaskService
{
    public const TOOL_UPDATE = 'update_task';

    public const STATUS_OPEN = 'open';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DONE = 'done';

    public const DESCRIPTION_LIMIT = 300;

    public function __construct(
        private readonly AppDashboardService $dashboard,
        private readonly TaskService $taskService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    public function myTasks(User $user, string $status, ?int $projectId, ?string $search, ?int $limit): AiTaskListData
    {
        $max = (int) config('ai.list_limit');
        $limit = $limit === null || $limit < 1 ? $max : min($limit, $max);

        $tasks = $this->visible($user, $this->ownTasks($user, $status, $projectId, $search)->get());

        return new AiTaskListData(
            data: $tasks->take($limit)->map(fn (Task $task): AiTaskData => $this->taskData($task))->values()->all(),
            total: $tasks->count(),
            limit: $limit,
            truncated: $tasks->count() > $limit,
        );
    }

    /**
     * Offene eigene Aufgaben mit Deadline bis Tagesende (inkl. ueberfaelliger), fuer die Tagesuebersicht.
     *
     * @return list<AiTaskData>
     */
    public function dueUntil(User $user, CarbonImmutable $day, int $limit): array
    {
        $tasks = $this->ownTasks($user, self::STATUS_OPEN, null, null)
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $day->endOfDay())
            ->get();

        return $this->visible($user, $tasks)
            ->take($limit)
            ->map(fn (Task $task): AiTaskData => $this->taskData($task))
            ->values()
            ->all();
    }

    /**
     * @return Builder<Task>
     */
    public function ownTasks(User $user, string $status, ?int $projectId, ?string $search): Builder
    {
        return $this->dashboard->ownTasksQuery($user)
            ->when($status === self::STATUS_DONE, static fn (Builder $query) => $query->where('done', true))
            ->when($status !== self::STATUS_DONE, static fn (Builder $query) => $query->where('done', false))
            ->when(
                $status === self::STATUS_OVERDUE,
                static fn (Builder $query) => $query->whereNotNull('deadline')->where('deadline', '<', now())
            )
            ->when(
                $projectId !== null,
                static fn (Builder $query) => $query->whereHas(
                    'checklist',
                    static fn (Builder $checklist) => $checklist->where('project_id', $projectId)
                )
            )
            ->when(
                $search !== null,
                static fn (Builder $query) => $query->where(
                    'name',
                    'like',
                    '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $search) . '%'
                )
            )
            ->with(['checklist.project', 'checklist.users', 'checklist.tasks.task_users'])
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC')
            ->orderBy('id');
    }

    public function update(
        User $user,
        int $taskId,
        bool $done,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $task = Task::query()
            ->with(['checklist.project', 'checklist.users', 'checklist.tasks.task_users'])
            ->find($taskId);
        abort_if($task === null || !$this->mayUpdate($user, $task), Response::HTTP_NOT_FOUND);

        $items = [$this->item($task, $done)];
        $summary = new AiWriteSummaryData(self::TOOL_UPDATE, 1, 0, 0);

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_UPDATE, $items, $summary);
        }

        $this->actionGuard->assertExecutable($user, (string) $intent->actionId, self::TOOL_UPDATE, $intent->args);

        DB::beginTransaction();

        try {
            $before = $task->getRawOriginal();

            if ((bool) $task->done !== $done) {
                $this->taskService->doneOrUndoneTask($task, $user->id);
            }

            $batch = $this->batchRecorder->record($user, self::TOOL_UPDATE, [[
                'model_type' => Task::class,
                'model_id' => (int) $task->id,
                'action' => 'update',
                'before_state' => $before,
            ]]);
            $this->actionGuard->complete($user, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        if ($task->checklist?->project_id !== null) {
            SafeBroadcast::send(new ChecklistUpdated($task->checklist->project_id), true);
        }

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_UPDATE,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: [(int) $task->id],
            items: $items,
            links: [],
            summary: $summary,
        );
    }

    public function taskData(Task $task): AiTaskData
    {
        $deadline = $task->deadline === null ? null : CarbonImmutable::parse($task->deadline);
        $checklist = $task->checklist;

        return new AiTaskData(
            id: (int) $task->id,
            name: AiText::truncate($task->name),
            description: $task->description === null ? null : mb_strimwidth(
                (string) $task->description,
                0,
                self::DESCRIPTION_LIMIT,
                '…'
            ),
            deadline: $deadline?->setTimezone((string) config('app.timezone'))->toIso8601String(),
            overdue: !$task->done && $deadline !== null && $deadline->isPast(),
            done: (bool) $task->done,
            checklist: new AiRefData((int) $task->checklist_id, AiText::truncate($checklist?->name)),
            project: AiRefData::make($checklist?->project_id, $checklist?->project?->name),
        );
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return Collection<int, Task>
     */
    private function visible(User $user, Collection $tasks): Collection
    {
        return $tasks->filter(fn (Task $task): bool => $task->checklist !== null && WebGuardScope::run(
            static fn (): bool => Gate::forUser($user)->allows('view', $task->checklist)
        ))->values();
    }

    /**
     * Aufgabe aus der eigenen Menge (sichtbar) oder Checkliste darf bearbeitet werden
     * (`ChecklistPolicy::update`) — sonst 404 wie bei unsichtbaren Objekten.
     */
    private function mayUpdate(User $user, Task $task): bool
    {
        if ($task->checklist === null) {
            return false;
        }

        if ($this->visible($user, $this->dashboard->ownTasksQuery($user)->whereKey($task->id)->get())->isNotEmpty()) {
            return true;
        }

        return WebGuardScope::run(static fn (): bool => Gate::forUser($user)->allows('update', $task->checklist));
    }

    private function item(Task $task, bool $done): AiWriteItemData
    {
        return new AiWriteItemData(
            action: 'update',
            id: (int) $task->id,
            label: AiText::truncate(($done ? 'Erledigt: ' : 'Wieder offen: ') . $task->name),
            start: null,
            end: null,
            room: null,
            conflicts: [],
        );
    }
}

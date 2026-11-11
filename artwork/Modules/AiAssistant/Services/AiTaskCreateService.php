<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteItemData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteSummaryData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\AiAssistant\Support\WebGuardScope;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Events\ChecklistUpdated;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /api/v1/ai/tasks` — Werkzeug `create_task` (ai-scenarios-4 S10).
 *
 * Ziel-Checkliste:
 * - `checklist_id`: sichtbar (`ChecklistPolicy::view`, sonst 404) und bearbeitbar
 *   (`ChecklistPolicy::update`, sonst `missing_permission` → 403) — wie `TaskController::store()`.
 * - `project_id`: Projekt sichtbar (sonst 404); erste nicht private Checkliste des Projekts, die die
 *   Person bearbeiten darf, sonst eine neue Checkliste „KI-Aufgaben" im Projekt (braucht
 *   `ProjectPolicy::createProperties` wie `ChecklistController::store()`).
 * - ohne beides: die private Checkliste der Person (eigene Checkliste ohne Projekt), sonst eine neue
 *   private „KI-Aufgaben".
 *
 * Angelegt wird ueber `TaskService::createTaskByRequest()` wie in der Oberflaeche (die anlegende
 * Person steht danach mit in der Aufgabe, Zugewiesene kommen bei Projekt-Checklisten ins Projektteam
 * und werden darueber benachrichtigt). Danach Verlauf, „Aufgabe hinzugefuegt"-Benachrichtigung wie
 * `TaskController::createNotificationForAllChecklistUser()` und — zusaetzlich — an die Zugewiesenen,
 * Broadcast ueber `SafeBroadcast`. Weil der Dienst synchron benachrichtigt, beschreibt die Vorschau
 * nur, was passieren wird. Undo loescht die Aufgabe (und eine dafuer angelegte Checkliste).
 */
class AiTaskCreateService
{
    public const TOOL_CREATE = 'create_task';

    public const NEW_CHECKLIST_NAME = 'KI-Aufgaben';

    public function __construct(
        private readonly TaskService $taskService,
        private readonly ChangeService $changeService,
        private readonly SchedulingService $schedulingService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<int> $assigneeIds
     */
    public function create(
        User $user,
        string $name,
        ?string $description,
        ?string $deadline,
        ?int $checklistId,
        ?int $projectId,
        array $assigneeIds,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $target = $this->target($user, $checklistId, $projectId);
        $item = $this->item($user, $name, $deadline, $target, $assigneeIds);
        $summary = new AiWriteSummaryData(
            self::TOOL_CREATE,
            1,
            $item->conflicts === [] ? 0 : 1,
            $target['allowed'] ? 0 : 1
        );

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_CREATE, [$item], $summary);
        }

        abort_unless($target['allowed'], Response::HTTP_FORBIDDEN);
        $this->actionGuard->assertExecutable($user, (string) $intent->actionId, self::TOOL_CREATE, $intent->args);

        DB::beginTransaction();

        try {
            $checklist = $target['checklist'] ?? $this->createChecklist($user, $target['project']);
            $task = $this->taskService->createTaskByRequest(
                $checklist,
                $name,
                $user->id,
                $description,
                $deadline,
                $assigneeIds,
            );

            if ($checklist->project_id !== null) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($checklist->project_id)
                        ->setTranslationKey('Task added to')
                        ->setTranslationKeyPlaceholderValues([$name, $checklist->name])
                );
            }

            $this->notify($user, $checklist, $assigneeIds);

            $batchItems = [[
                'model_type' => Task::class,
                'model_id' => (int) $task->id,
                'action' => 'create',
                'before_state' => null,
            ]];
            if ($target['checklist'] === null) {
                $batchItems[] = [
                    'model_type' => Checklist::class,
                    'model_id' => (int) $checklist->id,
                    'action' => 'create',
                    'before_state' => null,
                ];
            }

            $batch = $this->batchRecorder->record($user, self::TOOL_CREATE, $batchItems);
            $this->actionGuard->complete($user, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        if ($checklist->project_id !== null) {
            SafeBroadcast::send(new ChecklistUpdated((int) $checklist->project_id), true);
        }

        $result = new AiWriteItemData('create', (int) $task->id, $item->label, null, $item->end, null, []);

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_CREATE,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: [(int) $task->id],
            items: [$result],
            links: [],
            summary: new AiWriteSummaryData(self::TOOL_CREATE, 1, 0, 0),
        );
    }

    /**
     * @return array{checklist: Checklist|null, project: Project|null, allowed: bool, name: string}
     */
    private function target(User $user, ?int $checklistId, ?int $projectId): array
    {
        if ($checklistId !== null) {
            $checklist = Checklist::query()->with(['project', 'users', 'tasks.task_users'])->find($checklistId);
            abort_if($checklist === null || !$this->allows($user, 'view', $checklist), Response::HTTP_NOT_FOUND);

            return [
                'checklist' => $checklist,
                'project' => $checklist->project,
                'allowed' => $this->allows($user, 'update', $checklist),
                'name' => (string) $checklist->name,
            ];
        }

        if ($projectId !== null) {
            $project = Project::query()->visibleFor($user)->find($projectId);
            abort_if($project === null, Response::HTTP_NOT_FOUND);

            $checklist = Checklist::query()
                ->where('project_id', $project->id)
                ->where(static fn ($query) => $query->where('private', false)->orWhereNull('private'))
                ->with(['project', 'users', 'tasks.task_users'])
                ->orderBy('id')
                ->get()
                ->first(fn (Checklist $candidate): bool => $this->allows($user, 'update', $candidate));

            return [
                'checklist' => $checklist,
                'project' => $project,
                'allowed' => $checklist !== null || $this->allows($user, 'createProperties', $project),
                'name' => $checklist?->name ?? self::NEW_CHECKLIST_NAME,
            ];
        }

        $checklist = Checklist::query()
            ->whereNull('project_id')
            ->where('user_id', $user->id)
            ->orderByDesc('private')
            ->orderBy('id')
            ->first();

        return [
            'checklist' => $checklist,
            'project' => null,
            'allowed' => true,
            'name' => $checklist?->name ?? self::NEW_CHECKLIST_NAME,
        ];
    }

    private function allows(User $user, string $ability, Checklist|Project $model): bool
    {
        return WebGuardScope::run(static fn (): bool => Gate::forUser($user)->allows($ability, $model));
    }

    /**
     * Wie `ChecklistController::createWithoutTemplate()` (ohne Aufgaben aus dem Formular).
     */
    private function createChecklist(User $user, ?Project $project): Checklist
    {
        /** @var Checklist $checklist */
        $checklist = Checklist::query()->create([
            'name' => self::NEW_CHECKLIST_NAME,
            'project_id' => $project?->id,
            'user_id' => $user->id,
            'tab_id' => null,
            'private' => $project === null,
        ]);

        if ($project !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Checklist added')
                    ->setTranslationKeyPlaceholderValues([self::NEW_CHECKLIST_NAME])
            );
        }

        return $checklist;
    }

    /**
     * „Aufgabe hinzugefuegt" wie `TaskController::createNotificationForAllChecklistUser()`, plus an
     * die Zugewiesenen (die die Oberflaeche ueber die Checkliste erreicht).
     *
     * @param list<int> $assigneeIds
     */
    private function notify(User $user, Checklist $checklist, array $assigneeIds): void
    {
        $recipients = $checklist->user_id === null
            ? $checklist->users()->pluck('users.id')->map(static fn ($id): int => (int) $id)->all()
            : [(int) $user->id];

        foreach (array_unique(array_merge($recipients, $assigneeIds)) as $recipient) {
            $this->schedulingService->create($recipient, 'TASK_ADDED', 'TASKS', (int) $checklist->id, $user->id);
        }
    }

    /**
     * @param array{checklist: Checklist|null, project: Project|null, allowed: bool, name: string} $target
     * @param list<int> $assigneeIds
     */
    private function item(
        User $user,
        string $name,
        ?string $deadline,
        array $target,
        array $assigneeIds
    ): AiWriteItemData {
        $conflicts = [];
        $project = $target['project'];
        $projectRef = AiRefData::make($project?->id, $project?->name);

        if (!$target['allowed']) {
            $conflicts[] = new AiWriteConflictData(
                AiWriteConflictData::MISSING_PERMISSION,
                $target['checklist'] === null
                    ? 'Diese Person darf in diesem Projekt keine Checkliste anlegen.'
                    : 'Diese Person darf diese Checkliste nicht bearbeiten.',
                null
            );
        }

        if ($target['checklist'] === null) {
            $conflicts[] = new AiWriteConflictData(
                AiWriteConflictData::NEW_CHECKLIST,
                'Es wird eine neue Checkliste „' . self::NEW_CHECKLIST_NAME . '" angelegt.',
                null
            );
        }

        if ($project !== null) {
            $team = $project->users()->pluck('users.id')->map(static fn ($id): int => (int) $id)->all();
            $added = array_diff(array_merge($assigneeIds, [(int) $user->id]), $team);
            if ($added !== []) {
                $names = User::query()->whereIn('id', $added)->get()
                    ->map(static fn (User $person): string => $person->getFullNameAttribute())->implode(', ');
                $conflicts[] = new AiWriteConflictData(
                    AiWriteConflictData::ADDED_TO_PROJECT_TEAM,
                    'Wird ins Projektteam aufgenommen: ' . $names,
                    $projectRef
                );
            }
        }

        $assignees = User::query()->whereIn('id', $assigneeIds)->get()
            ->map(static fn (User $person): string => $person->getFullNameAttribute())->implode(', ');
        $timezone = (string) config('app.timezone');

        return new AiWriteItemData(
            action: 'create',
            id: null,
            label: AiText::truncate(
                'Aufgabe „' . $name . '" in ' . $target['name']
                . ($project === null ? '' : ' (' . $project->name . ')')
                . ($deadline === null ? '' : ', fällig ' . CarbonImmutable::parse($deadline)->format('d.m.Y'))
                . ($assignees === '' ? '' : ', für ' . $assignees)
            ),
            start: null,
            end: $deadline === null
                ? null
                : CarbonImmutable::parse($deadline, $timezone)->endOfDay()->toIso8601String(),
            room: null,
            conflicts: $conflicts,
        );
    }
}

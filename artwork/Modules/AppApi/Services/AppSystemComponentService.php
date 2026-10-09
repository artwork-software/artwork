<?php

namespace Artwork\Modules\AppApi\Services;

use App\Settings\ShiftSettings;
use Artwork\Modules\AppApi\Enums\WorkerType;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\Project\Services\ProjectTabDocumentService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;

/**
 * Builds the read-only `value` payloads of the system tab components
 * (schedule, shifts, checklists, comments, …) for the app tab renderer.
 * The data recipes mirror ProjectPrintLayoutController::show(); checklist
 * visibility mirrors ProjectTabChecklistService.
 */
class AppSystemComponentService
{
    private const EVENT_RELATIONS = [
        'room:id,name',
        'event_type:id,name,abbreviation,hex_code',
        'eventStatus:id,color',
        'creator:id',
    ];

    private const SHIFT_RELATIONS = [
        'craft',
        'room:id,name',
        'event:id,eventName',
        'users',
        'freelancer',
        'serviceProvider',
        'shiftsQualifications.shiftQualification',
    ];

    public function __construct(
        private readonly ShiftSettings $shiftSettings,
        private readonly ProjectComponentVisibilityService $visibility,
        private readonly ProjectTabDocumentService $documents,
    ) {
    }

    /**
     * @param array<int, int> $scope ComponentInTab scope (tab ids) for checklist and document components.
     * @return array<string, mixed>|null Null for types the app renders from the project header itself.
     */
    public function valueFor(User $user, Project $project, ProjectTabComponentEnum $type, array $scope): ?array
    {
        return match ($type) {
            // BULK_EDIT is the web's "Schedule" tab (bulk event editor) — on
            // app both render as the same event list.
            ProjectTabComponentEnum::CALENDAR,
            ProjectTabComponentEnum::BULK_EDIT => [
                'days' => $this->eventDays($user, $project),
                ...$this->eventEditorOptions(),
            ],
            ProjectTabComponentEnum::SHIFT_TAB => [
                'days' => $this->shiftDays($project),
                // Instance setting — when false the app must not offer the
                // overbooking confirmation at all (parity with the web planner).
                'overbooking_allowed' => $this->shiftSettings->allow_shift_overbooking,
                ...$this->shiftEditorOptions(),
            ],
            ProjectTabComponentEnum::CHECKLIST => ['checklists' => $this->checklists($user, $project, $scope)],
            ProjectTabComponentEnum::CHECKLIST_ALL => ['checklists' => $this->checklists($user, $project, [])],
            ProjectTabComponentEnum::COMMENT_TAB,
            ProjectTabComponentEnum::COMMENT_ALL_TAB => ['comments' => $this->comments($user, $project)],
            ProjectTabComponentEnum::PROJECT_DOCUMENTS => ['files' => $this->files($user, $project, $scope)],
            ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS => ['files' => $this->files($user, $project, [])],
            ProjectTabComponentEnum::PROJECT_ATTRIBUTES => [
                'categories' => $project->categories->pluck('name')->all(),
                'genres' => $project->genres->pluck('name')->all(),
                'sectors' => $project->sectors->pluck('name')->all(),
            ],
            ProjectTabComponentEnum::PROJECT_PERIOD => [
                'first' => $project->first_and_last_event_date['first_event_date'] ?? null,
                'last' => $project->first_and_last_event_date['last_event_date'] ?? null,
            ],
            ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION => [
                'text' => $project->shift_description,
            ],
            ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS => [
                'names' => $project->shift_contact->map(static fn (User $contact) => $contact->full_name)->all(),
            ],
            ProjectTabComponentEnum::PROJECT_BUDGET_DEADLINE => [
                // ISO date on the wire — display formatting is the app's job.
                'date' => $project->budget_deadline
                    ? Carbon::parse($project->budget_deadline)->format('Y-m-d')
                    : null,
            ],
            ProjectTabComponentEnum::BUDGET_INFORMATIONS => [
                'cost_center' => $project->costCenter?->name,
                'description' => $project->cost_center_description ?: null,
                'gema' => (bool) $project->gema,
            ],
            // Rendered client-side from the project header payload.
            default => null,
        };
    }

    /**
     * Whether the user may write through a system component: create events on
     * the schedule (per-event editability is flagged on each event), edit
     * shifts, or add comments. Mirrors the web's guards (EventPolicy,
     * ShiftController's shift-planner check, CommentPolicy).
     */
    public function isWritable(User $user, ProjectTabComponentEnum $type): bool
    {
        return match ($type) {
            ProjectTabComponentEnum::CALENDAR,
            ProjectTabComponentEnum::BULK_EDIT => $user->can('create', Event::class),
            ProjectTabComponentEnum::SHIFT_TAB => $user->can('plan-shifts'),
            ProjectTabComponentEnum::COMMENT_TAB,
            ProjectTabComponentEnum::COMMENT_ALL_TAB => true,
            default => false,
        };
    }

    /**
     * A single event with its relations loaded — the response of the event
     * endpoints, identical to an entry of the schedule component.
     *
     * @return array<string, mixed>
     */
    public function eventPayload(User $user, Event $event): array
    {
        return $this->mapEvent($user, $event->load(self::EVENT_RELATIONS));
    }

    /**
     * A single shift re-read from the database — the response of the shift
     * endpoints after a write, identical to an entry of the shift component.
     *
     * @return array<string, mixed>
     */
    public function shiftPayload(Shift $shift): array
    {
        return $this->mapShift($shift->refresh()->load(self::SHIFT_RELATIONS));
    }

    /**
     * @return array<string, mixed>
     */
    public function mapEvent(User $user, Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->eventName,
            'start' => $event->start_time->toIso8601String(),
            'end' => $event->end_time->toIso8601String(),
            'all_day' => (bool) $event->allDay,
            'event_type' => $event->event_type === null ? null : [
                'id' => $event->event_type->id,
                'name' => $event->event_type->name,
                'abbreviation' => $event->event_type->abbreviation,
                'hex_code' => $event->event_type->hex_code,
            ],
            'room' => $event->room === null ? null : ['id' => $event->room->id, 'name' => $event->room->name],
            'status_color' => $event->eventStatus?->color,
            'can_edit' => $user->can('update', $event),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapShift(Shift $shift): array
    {
        $workers = collect([...$shift->users, ...$shift->freelancer, ...$shift->serviceProvider])
            ->map(static fn (User|Freelancer|ServiceProvider $worker): array => [
                'id' => $worker->id,
                'type' => WorkerType::of($worker)->value,
                'name' => WorkerType::displayName($worker),
                'qualification_id' => $worker->pivot->shift_qualification_id,
                'is_overbooked' => (bool) $worker->pivot->is_overbooked,
            ])
            ->all();

        return [
            'id' => $shift->id,
            'date' => $shift->start_date === null
                ? null
                : Carbon::parse($shift->start_date)->toDateString(),
            'end_date' => $shift->end_date === null
                ? null
                : Carbon::parse($shift->end_date)->toDateString(),
            'start' => $shift->start,
            'end' => $shift->end,
            'break_minutes' => (int) $shift->break_minutes,
            'description' => $shift->description ?: null,
            'craft' => $shift->craft === null ? null : [
                'id' => $shift->craft->id,
                'name' => $shift->craft->name,
                'abbreviation' => $shift->craft->abbreviation,
                'color' => $shift->craft->color,
            ],
            'room' => $shift->room === null ? null : ['id' => $shift->room->id, 'name' => $shift->room->name],
            'event' => $shift->event?->eventName,
            'is_committed' => (bool) $shift->is_committed,
            'required_count' => (int) $shift->shiftsQualifications->sum('value'),
            'assigned_count' => count($workers),
            'workers' => $workers,
            // The shift's qualification slots with their staffing, for the
            // detail view's open-slot lines and the assignment picker.
            // `assigned` counts regular bookings only — overbooked workers do
            // not occupy a regular slot (same rule as the web planner).
            'qualifications' => $shift->shiftsQualifications
                ->map(static function ($slot) use ($workers): array {
                    $onSlot = collect($workers)->where('qualification_id', $slot->shift_qualification_id);

                    return [
                        'id' => $slot->shift_qualification_id,
                        'name' => $slot->shiftQualification?->name,
                        'required' => (int) $slot->value,
                        'assigned' => $onSlot->where('is_overbooked', false)->count(),
                        'overbooked' => $onSlot->where('is_overbooked', true)->count(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: int, name: string, done: bool, deadline: string|null}
     */
    public function mapTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'done' => (bool) $task->done,
            'deadline' => $task->deadline?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapComment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'text' => $comment->text,
            'author' => $comment->user?->full_name,
            'date' => $comment->created_at?->toIso8601String(),
        ];
    }

    /**
     * Crafts, rooms and qualifications the shift editor offers as pickers.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function shiftEditorOptions(): array
    {
        return [
            'crafts' => Craft::query()->orderBy('name')->get()
                ->map(static fn (Craft $craft): array => [
                    'id' => $craft->id,
                    'name' => $craft->name,
                    'abbreviation' => $craft->abbreviation,
                    'color' => $craft->color,
                ])
                ->all(),
            'rooms' => Room::query()->orderBy('name')->get(['id', 'name'])
                ->map(static fn (Room $room): array => ['id' => $room->id, 'name' => $room->name])
                ->all(),
            'shift_qualifications' => ShiftQualification::query()
                ->available()
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(static fn (ShiftQualification $qualification): array => [
                    'id' => $qualification->id,
                    'name' => $qualification->name,
                ])
                ->all(),
        ];
    }

    /**
     * Rooms and event types the event editor offers as pickers.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function eventEditorOptions(): array
    {
        return [
            'rooms' => Room::query()->orderBy('name')->get(['id', 'name'])
                ->map(static fn (Room $room): array => ['id' => $room->id, 'name' => $room->name])
                ->all(),
            'event_types' => EventType::query()->orderBy('name')->get()
                ->map(static fn (EventType $type): array => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'abbreviation' => $type->abbreviation,
                    'hex_code' => $type->hex_code,
                ])
                ->all(),
        ];
    }

    /**
     * Project files with short-lived signed download URLs, so the device can
     * open them without carrying the API token into a browser. Same rules as
     * the web document lists: visible tabs only, budget documents only for
     * people who would see them in the budget informations
     * (ProjectTabDocumentService). The URL carries the user id so the download
     * re-checks ProjectFilePolicy::view (AppProjectFileController).
     *
     * @param array<int, int> $scope
     * @return array<int, array<string, mixed>>
     */
    private function files(User $user, Project $project, array $scope): array
    {
        return $project->project_files()
            ->tap(fn ($q) => $this->constrainToVisibleScope($q, $user, $scope))
            ->tap(fn ($q) => $this->documents->constrainToVisibleBudgetDocuments($q, $project, $user))
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn (ProjectFile $file): array => [
                'id' => $file->id,
                'name' => $file->name,
                'created_at' => $file->created_at?->toIso8601String(),
                'url' => URL::temporarySignedRoute(
                    'app.v1.files.download',
                    now()->addMinutes(30),
                    ['projectFile' => $file->id, 'user' => $user->id],
                ),
            ])
            ->all();
    }

    /**
     * The project's events grouped by start day (planned events excluded, like
     * the web calendar). Multi-day events appear once, on their start day.
     *
     * @return array<int, array<string, mixed>>
     */
    private function eventDays(User $user, Project $project): array
    {
        return $project->events()
            ->with(self::EVENT_RELATIONS)
            ->where('is_planning', false)
            ->orderBy('start_time')
            ->get()
            ->groupBy(static fn (Event $event): string => $event->start_time->format('Y-m-d'))
            ->map(fn ($events, string $date): array => [
                'date' => $date,
                'events' => $events
                    ->map(fn (Event $event): array => $this->mapEvent($user, $event))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * The project's shifts grouped by day, with the staffing counter of the
     * web list view (workers vs. sum of qualification requirements).
     *
     * @return array<int, array<string, mixed>>
     */
    private function shiftDays(Project $project): array
    {
        return $project->shifts()
            ->with(self::SHIFT_RELATIONS)
            ->orderBy('start_date')
            ->orderBy('start')
            ->get()
            ->groupBy(static fn (Shift $shift): string => Carbon::parse($shift->start_date)->toDateString())
            ->map(fn ($shifts, string $date): array => [
                'date' => $date,
                'shifts' => $shifts
                    ->map(fn (Shift $shift): array => $this->mapShift($shift))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Checklist visibility mirrors ProjectTabChecklistService: public lists are
     * visible to everyone who sees the component (scoped to the component's tab
     * scope), private lists require involvement or project team membership.
     *
     * @param array<int, int> $scope
     * @return array<int, array<string, mixed>>
     */
    private function checklists(User $user, Project $project, array $scope): array
    {
        $isTeamMember = $project->users()->whereKey($user->id)->exists();

        return $project->checklists()
            ->with(['users:id', 'tasks' => static fn ($q) => $q->orderBy('order'), 'tasks.task_users:id'])
            ->tap(fn ($q) => $this->constrainToVisibleScope($q, $user, $scope))
            ->get()
            ->filter(static function (Checklist $checklist) use ($user, $isTeamMember): bool {
                if (!$checklist->private) {
                    return true;
                }
                return $checklist->user_id === $user->id
                    || $isTeamMember
                    || $checklist->users->contains('id', $user->id)
                    || $checklist->tasks->contains(
                        static fn (Task $task) => $task->task_users->contains('id', $user->id),
                    );
            })
            ->map(fn (Checklist $checklist): array => [
                'id' => $checklist->id,
                'name' => $checklist->name,
                'private' => (bool) $checklist->private,
                // ChecklistPolicy::update — adding and ticking tasks.
                'can_edit' => $user->can('update', $checklist),
                'tasks' => $checklist->tasks->map(fn (Task $task): array => $this->mapTask($task))->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * All project comments the user may see, newest first (both comment
     * components show the full project thread, like the web).
     *
     * @return array<int, array<string, mixed>>
     */
    private function comments(User $user, Project $project): array
    {
        return $project->comments()
            ->tap(fn ($q) => $this->visibility->constrainToVisibleTabs($q, $user))
            ->with('user:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Comment $comment): array => $this->mapComment($comment))
            ->values()
            ->all();
    }

    /**
     * Like the web tab services: the component's tab scope without tabs the
     * user may not see; without a scope, content without a tab or from a
     * visible tab.
     *
     * @param array<int, int> $scope
     */
    private function constrainToVisibleScope(Builder|Relation $query, User $user, array $scope): void
    {
        if ($scope === []) {
            $this->visibility->constrainToVisibleTabs($query, $user);

            return;
        }

        $query->whereIn('tab_id', $this->visibility->restrictScopeToVisibleTabs($user, $scope));
    }
}

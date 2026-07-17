<?php

namespace App\Http\Controllers;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\Project\Services\ProjectDayAssignmentService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectDayAssignmentController extends Controller
{
    public function __construct(
        private readonly ProjectDayAssignmentService $projectDayAssignmentService,
    ) {
    }

    /**
     * Projektvorschläge für den Picker (Zeitraum-Treffer + freie Suche).
     */
    public function projectOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'required|array|min:1|max:366',
            'days.*' => 'date',
            'search' => 'nullable|string|max:255',
        ]);

        return new JsonResponse([
            'projects' => $this->projectDayAssignmentService->getProjectOptionsForDays(
                $validated['days'],
                $validated['search'] ?? null
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'worker_type' => 'required|integer|in:0,1,2',
            'worker_id' => 'required|integer',
            'type' => 'required|string|in:binding,wish',
            'full_period' => 'required|boolean',
            'days' => 'required_if:full_period,false|array|max:366',
            'days.*' => 'date',
        ]);

        $type = ProjectDayAssignmentType::from($validated['type']);
        $employableType = $this->resolveEmployableType($validated['worker_type']);

        // Existenz der Person sicherstellen — sonst entstehen verwaiste Zuordnungszeilen
        $employableType::query()->findOrFail((int) $validated['worker_id']);

        $this->authorizeMutation($type, $employableType, (int) $validated['worker_id'], isCreation: true);

        $project = Project::findOrFail($validated['project_id']);

        if ($validated['full_period']) {
            $period = $this->projectDayAssignmentService->resolveProjectPeriod($project);

            if ($period === null) {
                throw ValidationException::withMessages([
                    'project_id' => __('The project has no events yet, so there is no project period to assign.'),
                ]);
            }

            $dates = collect(CarbonPeriod::create($period['start'], $period['end']))
                ->map(static fn ($day) => $day->format('Y-m-d'))
                ->all();
        } else {
            $dates = collect($validated['days'])
                ->map(static fn ($day) => Carbon::parse($day)->format('Y-m-d'))
                ->all();
        }

        if ($type === ProjectDayAssignmentType::WISH) {
            $this->ensureNoAbsenceOnDates($employableType, (int) $validated['worker_id'], $dates);
        }

        $created = $validated['full_period']
            ? $this->projectDayAssignmentService->createFullPeriodAssignments(
                $project,
                $employableType,
                (int) $validated['worker_id'],
                $type
            )
            : $this->projectDayAssignmentService->createAssignments(
                $project,
                $employableType,
                (int) $validated['worker_id'],
                $type,
                $dates,
                false
            );

        return new JsonResponse([
            'created' => $created->count(),
            'skipped' => count($dates) - $created->count(),
        ]);
    }

    public function destroy(Request $request, ProjectDayAssignment $projectDayAssignment): JsonResponse
    {
        $wholeGroup = $request->boolean('whole_group');

        $this->authorizeMutation(
            ProjectDayAssignmentType::from($projectDayAssignment->type),
            $projectDayAssignment->employable_type,
            $projectDayAssignment->employable_id
        );

        $this->projectDayAssignmentService->deleteAssignment($projectDayAssignment, $wholeGroup);

        return new JsonResponse(['success' => true]);
    }

    public function fullPeriodForWorker(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_type' => ['required', 'integer', 'in:0,1,2'],
            'worker_id' => ['required', 'integer'],
        ]);
        $employableType = $this->resolveEmployableType((int) $validated['worker_type']);
        $employableType::query()->findOrFail((int) $validated['worker_id']);

        $groups = ProjectDayAssignment::withTrashed()
            ->forEmployable($employableType, (int) $validated['worker_id'])
            ->binding()
            ->where('is_full_period', true)
            ->where(static function ($query): void {
                $query->whereNull('deleted_at')->orWhereNotNull('superseded_by_shift_id');
            })
            ->get(['project_id', 'group_id'])
            ->unique('group_id')
            ->map(static fn (ProjectDayAssignment $assignment) => [
                'project_id' => $assignment->project_id,
                'group_id' => $assignment->group_id,
            ])
            ->values();

        return new JsonResponse(['assignments' => $groups]);
    }

    public function destroyFullPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_type' => ['required', 'integer', 'in:0,1,2'],
            'worker_id' => ['required', 'integer'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'group_id' => ['required', 'uuid'],
        ]);
        $employableType = $this->resolveEmployableType((int) $validated['worker_type']);
        $employableType::query()->findOrFail((int) $validated['worker_id']);

        $deleted = $this->projectDayAssignmentService->deleteFullPeriodAssignment(
            (int) $validated['project_id'],
            $employableType,
            (int) $validated['worker_id'],
            $validated['group_id']
        );

        abort_unless($deleted, 404);

        return new JsonResponse(['success' => true]);
    }

    public function acceptWish(ProjectDayAssignment $projectDayAssignment): JsonResponse
    {
        // Admins passieren via Gate::before
        abort_unless((bool) auth()->user()?->can(PermissionEnum::SHIFT_PLANNER->value), 403);

        abort_unless($projectDayAssignment->type === ProjectDayAssignmentType::WISH->value, 422);

        $this->projectDayAssignmentService->acceptWishGroup($projectDayAssignment);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Alle Zuordnungen/Wünsche eines Projekts für den Schichten-Tab —
     * flache Zeilen mit Worker-Basisdaten, Gruppierung übernimmt das Frontend.
     */
    public function forProject(Project $project): JsonResponse
    {
        $rows = ProjectDayAssignment::query()
            ->with('project:id,name')
            ->where('project_id', $project->id)
            ->orderBy('date')
            ->get();

        $groupBounds = $this->projectDayAssignmentService->getGroupBounds(
            $rows->pluck('group_id')->unique()->values()->all()
        );

        $workersByType = [
            User::class => User::query()
                ->whereIn('id', $rows->where('employable_type', User::class)->pluck('employable_id')->unique())
                ->get(['id', 'first_name', 'last_name', 'position', 'profile_photo_path'])
                ->keyBy('id'),
            Freelancer::class => Freelancer::query()
                ->whereIn('id', $rows->where('employable_type', Freelancer::class)->pluck('employable_id')->unique())
                ->get(['id', 'first_name', 'last_name', 'profile_image'])
                ->keyBy('id'),
            ServiceProvider::class => ServiceProvider::query()
                ->whereIn('id', $rows->where('employable_type', ServiceProvider::class)->pluck('employable_id')->unique())
                ->get(['id', 'provider_name', 'profile_image'])
                ->keyBy('id'),
        ];

        $assignments = $rows->map(function (ProjectDayAssignment $row) use ($groupBounds, $workersByType) {
            $worker = $workersByType[$row->employable_type]->get($row->employable_id);

            $serialized = ProjectDayAssignmentService::serializeAssignment($row, $groupBounds);
            $serialized['worker'] = match ($row->employable_type) {
                User::class => $worker ? [
                    'id' => $worker->id,
                    'type' => 0,
                    'name' => $worker->getFullNameAttribute(),
                    'first_name' => $worker->first_name,
                    'last_name' => $worker->last_name,
                    'position' => $worker->position,
                    'profile_photo_url' => $worker->profile_photo_url,
                ] : null,
                Freelancer::class => $worker ? [
                    'id' => $worker->id,
                    'type' => 1,
                    'name' => $worker->getNameAttribute(),
                    'profile_photo_url' => $worker->profile_photo_url,
                ] : null,
                default => $worker ? [
                    'id' => $worker->id,
                    'type' => 2,
                    'name' => $worker->provider_name,
                    'profile_photo_url' => $worker->profile_photo_url,
                ] : null,
            };

            return $serialized;
        })->filter(static fn (array $row) => $row['worker'] !== null)->values();

        return new JsonResponse(['assignments' => $assignments]);
    }

    /**
     * Zugeordnete/wünschende Personen für das Projekt einer Schicht an deren
     * Tagen — fürs Unbesetzt-Dropdown (Zugeordnete werden priorisiert).
     */
    public function forShift(\Artwork\Modules\Shift\Models\Shift $shift): JsonResponse
    {
        $projectId = $shift->project_id ?? $shift->event?->project_id;

        if ($projectId === null || !$shift->start_date || !$shift->end_date) {
            return new JsonResponse(['assignees' => []]);
        }

        $rows = ProjectDayAssignment::query()
            ->where('project_id', $projectId)
            ->betweenDates($shift->start_date, $shift->end_date)
            ->get(['id', 'employable_type', 'employable_id', 'type']);

        $assignees = $rows
            ->map(static fn (ProjectDayAssignment $row) => [
                'type' => match ($row->employable_type) {
                    Freelancer::class => 'freelancer',
                    ServiceProvider::class => 'service_provider',
                    default => 'user',
                },
                'id' => $row->employable_id,
                'assignment_type' => $row->type,
            ])
            // verbindlich gewinnt gegenüber Wunsch bei Duplikaten
            ->sortBy(static fn (array $row) => $row['assignment_type'] === 'binding' ? 0 : 1)
            ->unique(static fn (array $row) => $row['type'] . '_' . $row['id'])
            ->values();

        return new JsonResponse(['assignees' => $assignees]);
    }

    /**
     * Precheck fürs Event-Modal: Welche Einzeltag-Zuordnungen würden aufgelöst,
     * wenn der Termin auf die neuen Zeiten verschoben wird? (Confirm-Dialog)
     */
    public function rescheduleImpact(\Artwork\Modules\Event\Models\Event $event, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $project = $event->project;

        if ($project === null) {
            return new JsonResponse(['affected' => []]);
        }

        $targetProjectId = $request->has('project_id') ? ($validated['project_id'] ?? null) : $project->id;
        $period = (int) $targetProjectId === (int) $project->id
            ? $this->projectDayAssignmentService->computeHypotheticalPeriod(
                $project,
                $event,
                Carbon::parse($validated['start_time']),
                Carbon::parse($validated['end_time'])
            )
            : $this->projectDayAssignmentService->computePeriodWithoutEvent($project, $event);

        return new JsonResponse([
            'affected' => $this->projectDayAssignmentService->getOutOfPeriodSingleDayAssignments($project, $period),
        ]);
    }

    private function resolveEmployableType(int $workerType): string
    {
        return match ($workerType) {
            1 => Freelancer::class,
            2 => ServiceProvider::class,
            default => User::class,
        };
    }

    /**
     * Verbindliche Einträge nur mit Schichtplanungsrecht. Wünsche darf jede*r
     * ausschließlich für sich selbst ANLEGEN; löschen dürfen sie die Person
     * selbst oder Planer*innen (z. B. beim Aufräumen).
     */
    private function authorizeMutation(
        ProjectDayAssignmentType $type,
        string $employableType,
        int $employableId,
        bool $isCreation = false
    ): void {
        $user = auth()->user();
        // Admins passieren via Gate::before
        $isPlanner = (bool) $user?->can(PermissionEnum::SHIFT_PLANNER->value);

        if ($type === ProjectDayAssignmentType::BINDING) {
            abort_unless($isPlanner, 403);

            return;
        }

        $isSelf = $employableType === User::class && $user !== null && (int) $user->id === $employableId;

        abort_unless($isCreation ? $isSelf : ($isSelf || $isPlanner), 403);
    }

    /**
     * Wunsch + Abwesenheit schließen sich aus — mit verständlicher Meldung
     * statt rohem Fehler (Produktentscheidung 13.07.2026).
     *
     * @param array<string> $dates
     */
    private function ensureNoAbsenceOnDates(string $employableType, int $employableId, array $dates): void
    {
        $absenceDates = Vacation::query()
            ->where('vacationer_type', $employableType)
            ->where('vacationer_id', $employableId)
            ->whereIn('date', $dates)
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('d.m.Y'))
            ->unique()
            ->values();

        if ($absenceDates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'days' => __('A project wish is not possible because an absence is entered on :dates.', [
                    'dates' => $absenceDates->implode(', '),
                ]),
            ]);
        }
    }
}

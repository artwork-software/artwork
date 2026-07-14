<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectDayAssignmentService
{
    // Sicherung gegen versehentliche Massen-Anlage bei kaputten Projektzeiträumen
    public const MAX_FULL_PERIOD_DAYS = 730;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
    ) {
    }

    /**
     * Zuordnung für den gesamten Projektzeitraum (löst den Zeitraum auf und
     * materialisiert Tageszeilen mit is_full_period = true).
     */
    public function createFullPeriodAssignments(
        Project $project,
        string $employableType,
        int $employableId,
        ProjectDayAssignmentType $type
    ): Collection {
        $period = $this->resolveProjectPeriod($project);

        if ($period === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'project_id' => __('The project has no events yet, so there is no project period to assign.'),
            ]);
        }

        if ($period['start']->diffInDays($period['end']) > self::MAX_FULL_PERIOD_DAYS) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'project_id' => __('The project period is too long for a full period assignment.'),
            ]);
        }

        $dates = [];

        foreach (CarbonPeriod::create($period['start'], $period['end']) as $day) {
            $dates[] = $day->format('Y-m-d');
        }

        return $this->createAssignments($project, $employableType, $employableId, $type, $dates, true);
    }

    /**
     * Projektzeitraum (frühester Termin-Start bis spätester Termin-Ende) je Projekt.
     * Termine mit relevant_for_project_period bevorzugt, sonst alle Termine —
     * gleiche Semantik wie Project::getFirstAndLastEventDateAttribute, aber als
     * Batch über zwei Aggregat-Queries (Muster ProjectController::getProjectPeriods).
     *
     * @param array<int>|null $projectIds null = alle Projekte
     * @return SupportCollection<int, array{start: Carbon, end: Carbon}> keyed by project_id
     */
    public function getProjectPeriodsByIds(?array $projectIds = null): SupportCollection
    {
        $baseQuery = static function () use ($projectIds) {
            $query = DB::table('events')
                ->selectRaw('events.project_id, MIN(events.start_time) as min_start, MAX(events.end_time) as max_end')
                ->whereNull('events.deleted_at')
                ->whereNotNull('events.project_id')
                ->groupBy('events.project_id');

            if ($projectIds !== null) {
                $query->whereIn('events.project_id', $projectIds);
            }

            return $query;
        };

        $relevantPeriods = $baseQuery()
            ->join('event_types', 'events.event_type_id', '=', 'event_types.id')
            ->where('event_types.relevant_for_project_period', true)
            ->get()
            ->keyBy('project_id');

        $fallbackPeriods = $baseQuery()->get()->keyBy('project_id');

        return $fallbackPeriods->map(static function ($fallback, $projectId) use ($relevantPeriods) {
            $row = $relevantPeriods->get($projectId, $fallback);

            return [
                'start' => Carbon::parse($row->min_start)->startOfDay(),
                'end' => Carbon::parse($row->max_end)->startOfDay(),
            ];
        });
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null
     */
    public function resolveProjectPeriod(Project $project): ?array
    {
        return $this->getProjectPeriodsByIds([$project->id])->get($project->id);
    }

    /**
     * Projektvorschläge für den Picker: ohne Suchbegriff die Projekte, deren
     * Zeitraum alle angefragten Tage abdeckt; mit Suchbegriff eine Namens-/
     * Künstler*innen-Suche — jeweils mit Zeitraum und Abdeckungs-Flag annotiert.
     *
     * @param array<string> $days Y-m-d
     * @return array<int, array<string, mixed>>
     */
    public function getProjectOptionsForDays(array $days, ?string $search = null): array
    {
        $days = collect($days)->map(static fn ($day) => Carbon::parse($day)->startOfDay())->sort()->values();
        $minDay = $days->first();
        $maxDay = $days->last();

        if ($search !== null && trim($search) !== '') {
            $projects = app(ProjectService::class)->searchProjectsByNameOrArtists(trim($search));
            $periods = $this->getProjectPeriodsByIds($projects->pluck('id')->all());
        } else {
            $periods = $this->getProjectPeriodsByIds()
                ->filter(static function (array $period) use ($minDay, $maxDay) {
                    return $minDay !== null
                        && $period['start']->lte($minDay)
                        && $period['end']->gte($maxDay);
                });

            $projects = Project::query()
                ->whereIn('id', $periods->keys())
                ->where('is_group', false)
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'artists']);
        }

        return $projects
            ->map(static function (Project $project) use ($periods, $minDay, $maxDay) {
                $period = $periods->get($project->id);
                $coversAllDays = $period !== null
                    && $minDay !== null
                    && $period['start']->lte($minDay)
                    && $period['end']->gte($maxDay);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'artists' => $project->artists ?: null,
                    'period_start' => $period ? $period['start']->format('Y-m-d') : null,
                    'period_end' => $period ? $period['end']->format('Y-m-d') : null,
                    'covers_all_days' => $coversAllDays,
                ];
            })
            ->sortBy([
                ['covers_all_days', 'desc'],
                ['period_start', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Legt Zuordnungen/Wünsche als Tageszeilen an (eine group_id pro Vorgang).
     * Tage mit bereits aktiver identischer Zuordnung oder — bei verbindlichen
     * Einträgen — bereits bestehender Schicht desselben Projekts werden übersprungen;
     * bestehende Wünsche werden von verbindlichen Einträgen absorbiert.
     *
     * @param array<string> $dates Y-m-d
     */
    public function createAssignments(
        Project $project,
        string $employableType,
        int $employableId,
        ProjectDayAssignmentType $type,
        array $dates,
        bool $isFullPeriod
    ): Collection {
        $dates = collect($dates)
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return new Collection();
        }

        $existingDates = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable($employableType, $employableId)
            ->where('type', $type->value)
            ->whereIn('date', $dates)
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        $shiftCoveredDates = $type === ProjectDayAssignmentType::BINDING
            ? $this->getShiftCoveredDates($project->id, $employableType, $employableId, $dates->first(), $dates->last())
            : collect();

        $datesToCreate = $dates
            ->reject(static fn (string $date) => $existingDates->contains($date))
            ->reject(static fn (string $date) => $shiftCoveredDates->contains($date))
            ->values();

        if ($datesToCreate->isEmpty()) {
            return new Collection();
        }

        $groupId = (string) Str::uuid();
        $created = new Collection();

        DB::transaction(function () use (
            $project,
            $employableType,
            $employableId,
            $type,
            $datesToCreate,
            $isFullPeriod,
            $groupId,
            $created
        ): void {
            // Verbindliche Einträge absorbieren bestehende Wünsche derselben Person
            // für dasselbe Projekt an denselben Tagen.
            if ($type === ProjectDayAssignmentType::BINDING) {
                ProjectDayAssignment::query()
                    ->where('project_id', $project->id)
                    ->forEmployable($employableType, $employableId)
                    ->wish()
                    ->whereIn('date', $datesToCreate)
                    ->delete();
            }

            foreach ($datesToCreate as $date) {
                $created->push(ProjectDayAssignment::create([
                    'project_id' => $project->id,
                    'employable_type' => $employableType,
                    'employable_id' => $employableId,
                    'date' => $date,
                    'type' => $type->value,
                    'group_id' => $groupId,
                    'is_full_period' => $isFullPeriod,
                    'created_by' => Auth::id(),
                ]));
            }
        });

        if ($type === ProjectDayAssignmentType::BINDING) {
            $this->ensureUserInProjectTeam($project, $employableType, $employableId);

            $this->logProjectChange(
                $project,
                'Employee was assigned to the project in the shift plan',
                [
                    $this->getWorkerName($employableType, $employableId),
                    $this->formatDateSpanLabel($datesToCreate->first(), $datesToCreate->last()),
                ]
            );
        }

        $this->logAssignmentActivity(
            $created->first(),
            'created',
            $type === ProjectDayAssignmentType::BINDING
                ? '{0} was assigned to project {1} for {2}'
                : '{0} entered a project wish for {1} for {2}',
            [
                $this->getWorkerName($employableType, $employableId),
                $project->name,
                $this->formatDateSpanLabel($datesToCreate->first(), $datesToCreate->last()),
            ]
        );

        return $created;
    }

    /**
     * Löscht einen Einzeltag oder die ganze Gruppe einer Zuordnung.
     */
    public function deleteAssignment(ProjectDayAssignment $assignment, bool $wholeGroup): void
    {
        $assignment->loadMissing('project');

        $rows = $wholeGroup
            ? ProjectDayAssignment::query()->where('group_id', $assignment->group_id)->get()
            : new Collection([$assignment]);

        $firstDate = $rows->min('date');
        $lastDate = $rows->max('date');

        ProjectDayAssignment::query()->whereIn('id', $rows->pluck('id'))->delete();

        if ($assignment->isBinding() && $assignment->project) {
            $this->logProjectChange(
                $assignment->project,
                'Project assignment in the shift plan was removed',
                [
                    $this->getWorkerName($assignment->employable_type, $assignment->employable_id),
                    $this->formatDateSpanLabel($firstDate, $lastDate),
                ]
            );
        }

        $this->logAssignmentActivity(
            $assignment,
            'deleted',
            $assignment->isBinding()
                ? 'Project assignment of {0} for {1} ({2}) was removed'
                : 'Project wish of {0} for {1} ({2}) was removed',
            [
                $this->getWorkerName($assignment->employable_type, $assignment->employable_id),
                $assignment->project?->name ?? '',
                $this->formatDateSpanLabel($firstDate, $lastDate),
            ]
        );
    }

    /**
     * Wunsch annehmen: die ganze Gruppe wird zur verbindlichen Zuordnung.
     * Tage, an denen bereits eine verbindliche Zuordnung existiert, werden
     * absorbiert (Wunsch-Zeile entfällt).
     */
    public function acceptWishGroup(ProjectDayAssignment $assignment): void
    {
        $assignment->loadMissing('project');

        $wishRows = ProjectDayAssignment::query()
            ->where('group_id', $assignment->group_id)
            ->wish()
            ->get();

        if ($wishRows->isEmpty()) {
            return;
        }

        $existingBindingDates = ProjectDayAssignment::query()
            ->where('project_id', $assignment->project_id)
            ->forEmployable($assignment->employable_type, $assignment->employable_id)
            ->binding()
            ->whereIn('date', $wishRows->pluck('date'))
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        DB::transaction(static function () use ($wishRows, $existingBindingDates): void {
            foreach ($wishRows as $row) {
                if ($existingBindingDates->contains($row->date->format('Y-m-d'))) {
                    $row->delete();

                    continue;
                }

                $row->update(['type' => ProjectDayAssignmentType::BINDING->value]);
            }
        });

        if ($assignment->project) {
            $this->ensureUserInProjectTeam(
                $assignment->project,
                $assignment->employable_type,
                $assignment->employable_id
            );

            $this->logProjectChange(
                $assignment->project,
                'Project wish was accepted as binding assignment',
                [
                    $this->getWorkerName($assignment->employable_type, $assignment->employable_id),
                    $this->formatDateSpanLabel($wishRows->min('date'), $wishRows->max('date')),
                ]
            );
        }

        $this->logAssignmentActivity(
            $assignment,
            'wish_accepted',
            'Project wish of {0} for {1} ({2}) was accepted as binding assignment',
            [
                $this->getWorkerName($assignment->employable_type, $assignment->employable_id),
                $assignment->project?->name ?? '',
                $this->formatDateSpanLabel($wishRows->min('date'), $wishRows->max('date')),
            ]
        );
    }

    /**
     * Schicht-Zuweisung „verwandelt" die Projektzuordnung: Zeilen desselben
     * Projekts an den Schicht-Tagen werden mit Schicht-Verweis soft-gelöscht,
     * damit sie beim Entfernen der Schicht wiederhergestellt werden können.
     */
    public function supersedeForShiftAssignment(Shift $shift, string $employableType, int $employableId): void
    {
        $projectId = $this->resolveShiftProjectId($shift);

        if ($projectId === null || !$shift->start_date || !$shift->end_date) {
            return;
        }

        $assignments = ProjectDayAssignment::query()
            ->where('project_id', $projectId)
            ->forEmployable($employableType, $employableId)
            ->betweenDates($shift->start_date, $shift->end_date)
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->update(['superseded_by_shift_id' => $shift->id]);
            $assignment->delete();
        }
    }

    /**
     * Gegenstück zum Verwandeln: wird die Schicht-Zuweisung entfernt, kommt die
     * Projektzuordnung zurück — außer eine identische aktive Zeile existiert
     * bereits oder eine andere Schicht desselben Projekts deckt den Tag noch ab.
     */
    public function restoreForShiftRemoval(Shift $shift, string $employableType, int $employableId): void
    {
        $supersededRows = ProjectDayAssignment::onlyTrashed()
            ->where('superseded_by_shift_id', $shift->id)
            ->forEmployable($employableType, $employableId)
            ->get();

        if ($supersededRows->isEmpty()) {
            return;
        }

        $projectId = $supersededRows->first()->project_id;

        $stillCoveredDates = $this->getShiftCoveredDates(
            $projectId,
            $employableType,
            $employableId,
            $supersededRows->min('date')->format('Y-m-d'),
            $supersededRows->max('date')->format('Y-m-d'),
            excludeShiftId: $shift->id
        );

        $activeRows = ProjectDayAssignment::query()
            ->where('project_id', $projectId)
            ->forEmployable($employableType, $employableId)
            ->whereIn('date', $supersededRows->pluck('date')->map(static fn ($date) => $date->format('Y-m-d')))
            ->get();

        foreach ($supersededRows as $row) {
            $date = $row->date->format('Y-m-d');

            $hasActiveDuplicate = $activeRows->contains(
                static fn (ProjectDayAssignment $active) => $active->date->format('Y-m-d') === $date
                    && $active->type === $row->type
            );

            if ($hasActiveDuplicate || $stillCoveredDates->contains($date)) {
                continue;
            }

            $row->restore();
            $row->update(['superseded_by_shift_id' => null]);
        }
    }

    /**
     * Verfügbarkeits-/Abwesenheits-Einträge lösen Zuordnungen auf:
     * - „Frei" (FREE_WORK) löst verbindliche Zuordnungen UND Wünsche auf
     *   (verbindliche mit Planer-Notification + Schichtverlaufs-Eintrag),
     * - Abwesenheiten (OFF_WORK, NOT_AVAILABLE) lösen nur Wünsche auf.
     *
     * @param array<string> $dates Y-m-d
     */
    public function handleVacationEntry(
        User|Freelancer|ServiceProvider $vacationer,
        array $dates,
        string $vacationType
    ): void {
        if ($vacationType === \Artwork\Modules\Vacation\Enums\Vacation::AVAILABLE->value || $dates === []) {
            return;
        }

        $dissolvesBinding = $vacationType === 'FREE_WORK';
        $employableType = get_class($vacationer);

        $query = ProjectDayAssignment::query()
            ->with('project:id,name')
            ->forEmployable($employableType, $vacationer->id)
            ->whereIn('date', $dates);

        if (!$dissolvesBinding) {
            $query->wish();
        }

        $assignments = $query->get();

        if ($assignments->isEmpty()) {
            return;
        }

        ProjectDayAssignment::query()->whereIn('id', $assignments->pluck('id'))->delete();

        $dissolvedBindings = $assignments->filter(static fn (ProjectDayAssignment $row) => $row->isBinding());

        foreach ($dissolvedBindings->groupBy('project_id') as $rows) {
            $project = $rows->first()->project;
            $datesLabel = $rows
                ->map(static fn (ProjectDayAssignment $row) => $row->date->format('d.m.Y'))
                ->unique()
                ->implode(', ');
            $workerName = $this->getWorkerName($employableType, $vacationer->id);

            $this->notifyPlannersAboutDissolution($project, $workerName, $datesLabel, 'free_day');

            if ($project) {
                $this->logProjectChange(
                    $project,
                    'Project assignment was dissolved by a free day entry',
                    [$workerName, $datesLabel]
                );
            }

            // Bewusst auch in den Schichtverlauf (Spatie shift-Log): die Auflösung
            // einer verbindlichen Zuordnung durch einen Frei-Eintrag muss dort
            // nachvollziehbar sein (Produktentscheidung 13.07.2026).
            activity('shift')
                ->causedBy(Auth::user())
                ->event('project_assignment_dissolved')
                ->tap(function ($activity) use ($project, $workerName, $datesLabel): void {
                    $activity->properties = $activity->properties->merge([
                        'translation_key' => 'Project assignment of {0} for {1} ({2}) was dissolved by a free day entry',
                        'translation_key_placeholder_values' => [$workerName, $project?->name ?? '', $datesLabel],
                        'context' => 'normal',
                    ]);
                })
                ->log('Project assignment dissolved by free day entry');
        }
    }

    /**
     * Dienstplan-Payload: hängt project_assignments (Map Y-m-d => Einträge) an
     * die Worker-Einträge eines Typs — eine Batch-Query pro Worker-Typ.
     *
     * @param array<int, array<string, mixed>> $entries
     */
    public function attachAssignmentsToWorkerEntries(
        array $entries,
        string $workerKey,
        string $employableType,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $workerIds = collect($entries)
            ->map(static fn (array $entry) => $entry[$workerKey]['id'] ?? null)
            ->filter()
            ->all();

        if ($workerIds === []) {
            return $entries;
        }

        $assignmentsByWorker = $this->getAssignmentsGroupedByDate($employableType, $workerIds, $startDate, $endDate);

        foreach ($entries as &$entry) {
            $workerId = $entry[$workerKey]['id'] ?? null;
            $entry['project_assignments'] = $workerId !== null
                ? ($assignmentsByWorker->get($workerId) ?? new \stdClass())
                : new \stdClass();
        }

        return $entries;
    }

    /**
     * @param array<int> $employableIds
     * @return SupportCollection keyed by employable_id => Map(Y-m-d => array of serialized assignments)
     */
    public function getAssignmentsGroupedByDate(
        string $employableType,
        array $employableIds,
        Carbon $startDate,
        Carbon $endDate
    ): SupportCollection {
        $rows = ProjectDayAssignment::query()
            ->with('project:id,name')
            // Zuordnungen von Papierkorb-Projekten ausblenden (kommen beim Restore
            // des Projekts automatisch wieder — SoftDeletes-Scope auf der Relation)
            ->whereHas('project')
            ->where('employable_type', $employableType)
            ->whereIn('employable_id', $employableIds)
            ->betweenDates($startDate, $endDate)
            ->orderBy('date')
            ->get();

        $groupBounds = $this->getGroupBounds($rows->pluck('group_id')->unique()->values()->all());

        return $rows
            ->groupBy('employable_id')
            ->map(static function (Collection $workerRows) use ($groupBounds) {
                return $workerRows
                    ->groupBy(static fn (ProjectDayAssignment $row) => $row->date->format('Y-m-d'))
                    ->map(static function (Collection $dayRows) use ($groupBounds) {
                        return $dayRows
                            ->map(static fn (ProjectDayAssignment $row) => self::serializeAssignment($row, $groupBounds))
                            ->values();
                    });
            });
    }

    /**
     * Flache, serialisierte Zuordnungen einer Person im Zeitraum — z. B. für den
     * Verfügbarkeitskalender (Wünsche) oder Listen-Anzeigen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSerializedAssignmentsForEmployable(
        string $employableType,
        int $employableId,
        Carbon $startDate,
        Carbon $endDate,
        ?ProjectDayAssignmentType $filterType = null
    ): array {
        $rows = ProjectDayAssignment::query()
            ->with('project:id,name')
            ->whereHas('project')
            ->forEmployable($employableType, $employableId)
            ->betweenDates($startDate, $endDate)
            ->when($filterType !== null, static fn ($query) => $query->where('type', $filterType->value))
            ->orderBy('date')
            ->get();

        $groupBounds = $this->getGroupBounds($rows->pluck('group_id')->unique()->values()->all());

        return $rows
            ->map(static fn (ProjectDayAssignment $row) => self::serializeAssignment($row, $groupBounds))
            ->values()
            ->all();
    }

    /**
     * Serien-Grenzen (min/max Datum) je group_id über ALLE Tage der Gruppe —
     * auch außerhalb des sichtbaren Zeitraums (Muster attachSeriesDateBounds).
     *
     * @param array<string> $groupIds
     */
    public function getGroupBounds(array $groupIds): SupportCollection
    {
        if ($groupIds === []) {
            return collect();
        }

        return ProjectDayAssignment::query()
            ->selectRaw('group_id, MIN(date) as series_start, MAX(date) as series_end')
            ->whereIn('group_id', $groupIds)
            ->groupBy('group_id')
            ->get()
            ->keyBy('group_id');
    }

    public static function serializeAssignment(ProjectDayAssignment $row, SupportCollection $groupBounds): array
    {
        $bounds = $groupBounds->get($row->group_id);

        return [
            'id' => $row->id,
            'project_id' => $row->project_id,
            'project_name' => $row->project?->name ?? '',
            'type' => $row->type,
            'group_id' => $row->group_id,
            'is_full_period' => $row->is_full_period,
            'date' => $row->date->format('Y-m-d'),
            'series_start' => $bounds ? Carbon::parse($bounds->series_start)->format('Y-m-d') : $row->date->format('Y-m-d'),
            'series_end' => $bounds ? Carbon::parse($bounds->series_end)->format('Y-m-d') : $row->date->format('Y-m-d'),
        ];
    }

    /**
     * Tage im Bereich, die durch eine aktive Schicht desselben Projekts der
     * Person bereits abgedeckt sind (dort ist die Zuordnung bereits konkretisiert).
     *
     * @return SupportCollection<int, string> Y-m-d
     */
    private function getShiftCoveredDates(
        int $projectId,
        string $employableType,
        int $employableId,
        string $rangeStart,
        string $rangeEnd,
        ?int $excludeShiftId = null
    ): SupportCollection {
        $shiftRanges = ShiftWorker::query()
            ->where('employable_type', $employableType)
            ->where('employable_id', $employableId)
            ->when($excludeShiftId !== null, static fn ($query) => $query->where('shift_id', '!=', $excludeShiftId))
            ->whereHas('shift', static function ($query) use ($projectId, $rangeStart, $rangeEnd): void {
                $query
                    ->where(static function ($projectQuery) use ($projectId): void {
                        $projectQuery
                            ->where('project_id', $projectId)
                            ->orWhereHas('event', static function ($eventQuery) use ($projectId): void {
                                $eventQuery->where('project_id', $projectId);
                            });
                    })
                    ->whereDate('start_date', '<=', $rangeEnd)
                    ->whereDate('end_date', '>=', $rangeStart);
            })
            ->with('shift:id,start_date,end_date')
            ->get();

        $covered = collect();

        foreach ($shiftRanges as $pivot) {
            $shift = $pivot->shift;

            if (!$shift?->start_date || !$shift->end_date) {
                continue;
            }

            foreach (CarbonPeriod::create($shift->start_date, $shift->end_date) as $day) {
                $covered->push($day->format('Y-m-d'));
            }
        }

        return $covered->unique()->values();
    }

    private function resolveShiftProjectId(Shift $shift): ?int
    {
        return $shift->project_id ?? $shift->event?->project_id;
    }

    private function ensureUserInProjectTeam(Project $project, string $employableType, int $employableId): void
    {
        if ($employableType !== User::class) {
            return;
        }

        if (!$project->users()->where('users.id', $employableId)->exists()) {
            $project->users()->attach($employableId);
        }
    }

    private function notifyPlannersAboutDissolution(
        ?Project $project,
        string $workerName,
        string $datesLabel,
        string $reason
    ): void {
        $planners = User::permission(PermissionEnum::SHIFT_PLANNER->value)->get();

        foreach ($planners as $planner) {
            $notificationTitle = __(
                $reason === 'free_day'
                    ? 'notification.project_assignment.dissolved_by_free_day'
                    : 'notification.project_assignment.dissolved_by_reschedule',
                [
                    'workerName' => $workerName,
                    'projectName' => $project?->name ?? '',
                ],
                $planner->language
            );

            $this->notificationService->setNotificationTo($planner);
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setProjectId($project?->id);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
            $this->notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'error',
                'message' => $notificationTitle,
            ]);
            $this->notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => __('notification.keyWords.concerns', [], $planner->language) . $workerName,
                    'href' => null,
                ],
                2 => [
                    'type' => 'string',
                    'title' => $datesLabel,
                    'href' => null,
                ],
            ]);
            $this->notificationService->createNotification();
            $this->notificationService->clearNotificationData();
        }
    }

    /**
     * Auflösung durch Terminverschiebung: Einzeltag-Zuordnungen außerhalb des
     * neuen Projektzeitraums werden aufgelöst, Ganz-Zeitraum-Gruppen auf den
     * neuen Zeitraum re-materialisiert (Phase-5-Hook, siehe EventController).
     */
    public function rematerializeForProjectPeriodChange(Project $project): array
    {
        // Frühausstieg ohne Aggregat-Queries: der Event-Observer ruft diese Methode
        // bei jeder Termin-Mutation auf — die allermeisten Projekte haben keine Zuordnungen.
        if (!ProjectDayAssignment::query()->where('project_id', $project->id)->exists()) {
            return ['dissolved' => [], 'rematerialized' => 0];
        }

        $period = $this->resolveProjectPeriod($project);

        if ($period === null) {
            return ['dissolved' => [], 'rematerialized' => 0];
        }

        $dissolvedRows = [];

        // Einzeltag-Einträge außerhalb des neuen Zeitraums auflösen
        $outOfPeriodSingles = ProjectDayAssignment::query()
            ->with('project:id,name')
            ->where('project_id', $project->id)
            ->where('is_full_period', false)
            ->where(static function ($query) use ($period): void {
                $query
                    ->whereDate('date', '<', $period['start'])
                    ->orWhereDate('date', '>', $period['end']);
            })
            ->get();

        foreach ($outOfPeriodSingles->groupBy(static fn ($row) => $row->employable_type . '_' . $row->employable_id) as $rows) {
            $first = $rows->first();
            $workerName = $this->getWorkerName($first->employable_type, $first->employable_id);
            $datesLabel = $rows->map(static fn ($row) => $row->date->format('d.m.Y'))->unique()->implode(', ');

            ProjectDayAssignment::query()->whereIn('id', $rows->pluck('id'))->delete();

            if ($rows->contains(static fn (ProjectDayAssignment $row) => $row->isBinding())) {
                $this->notifyPlannersAboutDissolution($project, $workerName, $datesLabel, 'reschedule');
                $this->logProjectChange(
                    $project,
                    'Project assignment was dissolved because the project was rescheduled',
                    [$workerName, $datesLabel]
                );
            }

            $dissolvedRows[] = [
                'worker_name' => $workerName,
                'dates' => $datesLabel,
            ];
        }

        // Ganz-Zeitraum-Gruppen auf den neuen Zeitraum re-materialisieren
        $rematerialized = 0;
        $fullPeriodGroups = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->where('is_full_period', true)
            ->get()
            ->groupBy('group_id');

        foreach ($fullPeriodGroups as $groupId => $rows) {
            $first = $rows->first();
            $targetDates = collect();

            foreach (CarbonPeriod::create($period['start'], $period['end']) as $day) {
                $targetDates->push($day->format('Y-m-d'));
            }

            $existingDates = $rows->map(static fn ($row) => $row->date->format('Y-m-d'));

            $missingDates = $targetDates->reject(static fn ($date) => $existingDates->contains($date));
            $obsoleteRows = $rows->reject(static fn ($row) => $targetDates->contains($row->date->format('Y-m-d')));

            if ($missingDates->isEmpty() && $obsoleteRows->isEmpty()) {
                continue;
            }

            DB::transaction(static function () use ($first, $missingDates, $obsoleteRows, $groupId): void {
                if ($obsoleteRows->isNotEmpty()) {
                    ProjectDayAssignment::query()->whereIn('id', $obsoleteRows->pluck('id'))->delete();
                }

                foreach ($missingDates as $date) {
                    ProjectDayAssignment::create([
                        'project_id' => $first->project_id,
                        'employable_type' => $first->employable_type,
                        'employable_id' => $first->employable_id,
                        'date' => $date,
                        'type' => $first->type,
                        'group_id' => $groupId,
                        'is_full_period' => true,
                        'created_by' => $first->created_by,
                    ]);
                }
            });

            $rematerialized++;
        }

        return ['dissolved' => $dissolvedRows, 'rematerialized' => $rematerialized];
    }

    /**
     * Hypothetischer Projektzeitraum, wenn $event auf newStart/newEnd verschoben
     * würde — gleiche Relevanz-Semantik wie resolveProjectPeriod. Grundlage für
     * den Confirm-Dialog im Event-Modal (Precheck vor dem Speichern).
     *
     * @return array{start: Carbon, end: Carbon}|null
     */
    public function computeHypotheticalPeriod(
        Project $project,
        \Artwork\Modules\Event\Models\Event $event,
        Carbon $newStart,
        Carbon $newEnd
    ): ?array {
        $aggregate = function (bool $relevantOnly) use ($project, $event): ?array {
            $query = DB::table('events')
                ->selectRaw('MIN(events.start_time) as min_start, MAX(events.end_time) as max_end')
                ->whereNull('events.deleted_at')
                ->where('events.project_id', $project->id)
                ->where('events.id', '!=', $event->id);

            if ($relevantOnly) {
                $query
                    ->join('event_types', 'events.event_type_id', '=', 'event_types.id')
                    ->where('event_types.relevant_for_project_period', true);
            }

            $row = $query->first();

            return $row?->min_start
                ? ['start' => Carbon::parse($row->min_start), 'end' => Carbon::parse($row->max_end)]
                : null;
        };

        $mergeEventTimes = static function (?array $period) use ($newStart, $newEnd): array {
            if ($period === null) {
                return ['start' => $newStart->copy(), 'end' => $newEnd->copy()];
            }

            return [
                'start' => $period['start']->lte($newStart) ? $period['start'] : $newStart->copy(),
                'end' => $period['end']->gte($newEnd) ? $period['end'] : $newEnd->copy(),
            ];
        };

        $eventIsRelevant = (bool) $event->getAttribute('event_type')?->getAttribute('relevant_for_project_period');

        $relevantPeriod = $aggregate(true);

        if ($eventIsRelevant) {
            $relevantPeriod = $mergeEventTimes($relevantPeriod);
        }

        $period = $relevantPeriod ?? $mergeEventTimes($aggregate(false));

        return [
            'start' => $period['start']->startOfDay(),
            'end' => $period['end']->startOfDay(),
        ];
    }

    /**
     * Einzeltag-Zuordnungen, die außerhalb des (hypothetischen) Zeitraums liegen —
     * pro Person gruppiert, für die Anzeige im Confirm-Dialog.
     *
     * @param array{start: Carbon, end: Carbon} $period
     * @return array<int, array{worker_name: string, type: string, dates: array<string>}>
     */
    public function getOutOfPeriodSingleDayAssignments(Project $project, array $period): array
    {
        $rows = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->where('is_full_period', false)
            ->where(static function ($query) use ($period): void {
                $query
                    ->whereDate('date', '<', $period['start'])
                    ->orWhereDate('date', '>', $period['end']);
            })
            ->orderBy('date')
            ->get();

        return $rows
            ->groupBy(static fn (ProjectDayAssignment $row) => $row->employable_type . '_' . $row->employable_id . '_' . $row->type)
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'worker_name' => $this->getWorkerName($first->employable_type, $first->employable_id),
                    'type' => $first->type,
                    'dates' => $group->map(static fn ($row) => $row->date->format('d.m.Y'))->unique()->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function getWorkerName(string $employableType, int $employableId): string
    {
        $worker = $employableType::find($employableId);

        return match (true) {
            $worker instanceof User => $worker->getFullNameAttribute(),
            $worker instanceof Freelancer => $worker->getNameAttribute(),
            $worker instanceof ServiceProvider => $worker->getNameAttribute(),
            default => '',
        };
    }

    private function formatDateSpanLabel(mixed $firstDate, mixed $lastDate): string
    {
        $first = $firstDate instanceof Carbon ? $firstDate : Carbon::parse($firstDate);
        $last = $lastDate instanceof Carbon ? $lastDate : Carbon::parse($lastDate);

        if ($first->isSameDay($last)) {
            return $first->format('d.m.Y');
        }

        return $first->format('d.m.Y') . ' - ' . $last->format('d.m.Y');
    }

    private function logProjectChange(Project $project, string $translationKey, array $placeholderValues): void
    {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey($translationKey)
                ->setTranslationKeyPlaceholderValues($placeholderValues)
        );
    }

    private function logAssignmentActivity(
        ?ProjectDayAssignment $assignment,
        string $event,
        string $translationKey,
        array $placeholderValues
    ): void {
        $activity = activity('project_day_assignment')
            ->causedBy(Auth::user())
            ->event($event);

        if ($assignment !== null && $assignment->exists) {
            $activity->performedOn($assignment);
        }

        $activity
            ->tap(function ($activityModel) use ($translationKey, $placeholderValues): void {
                $activityModel->properties = $activityModel->properties->merge([
                    'translation_key' => $translationKey,
                    'translation_key_placeholder_values' => $placeholderValues,
                ]);
            })
            ->log($translationKey);
    }
}

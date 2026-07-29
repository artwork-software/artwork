<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Events\ProjectDayAssignmentsChanged;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
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

        $groupId = (string) Str::uuid();
        $created = new Collection();

        // Lock pro Projekt/Person: Duplikat-Prüfung und Insert müssen zusammen atomar
        // sein (Doppel-Submit/paralleler zweiter Planer erzeugt sonst doppelte Zeilen —
        // ein Unique-Index ist wegen SoftDeletes bewusst nicht möglich)
        $lock = \Illuminate\Support\Facades\Cache::lock(
            sprintf('pda-create-%d-%s-%d', $project->id, $employableType, $employableId),
            15
        );

        $datesToCreate = $lock->block(10, function () use (
            $project,
            $employableType,
            $employableId,
            $type,
            $dates,
            $isFullPeriod,
            $groupId,
            $created
        ) {
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
                return $datesToCreate;
            }

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

                $timestamp = now();
                ProjectDayAssignment::query()->insert($datesToCreate->map(static fn (string $date) => [
                        'project_id' => $project->id,
                        'employable_type' => $employableType,
                        'employable_id' => $employableId,
                        'date' => $date,
                        'type' => $type->value,
                        'group_id' => $groupId,
                        'is_full_period' => $isFullPeriod,
                        'created_by' => Auth::id(),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])->all());

                $created->push(...ProjectDayAssignment::query()
                    ->where('group_id', $groupId)
                    ->orderBy('date')
                    ->get()
                    ->all());
            });

            return $datesToCreate;
        });

        if ($datesToCreate->isEmpty()) {
            return new Collection();
        }

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

            $this->notifyPersonAboutBindingChange(
                'assigned',
                $project,
                $employableType,
                $employableId,
                $this->formatDateSpanLabel($datesToCreate->first(), $datesToCreate->last())
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

        $this->broadcastProjectAssignmentsChanged($project->id);

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

        // Bereits von Schichten supersedete (soft-gelöschte) Zeilen desselben Scopes
        // dürfen nach dem expliziten Löschen nicht per Schicht-Entzug wiederauferstehen
        ProjectDayAssignment::onlyTrashed()
            ->where('group_id', $assignment->group_id)
            ->whereNotNull('superseded_by_shift_id')
            ->when(!$wholeGroup, static fn ($query) => $query->where('date', $assignment->date->toDateString()))
            ->update(['superseded_by_shift_id' => null]);

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

        if ($assignment->isBinding()) {
            $this->notifyPersonAboutBindingChange(
                'removed',
                $assignment->project,
                $assignment->employable_type,
                $assignment->employable_id,
                $this->formatDateSpanLabel($firstDate, $lastDate)
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

        $this->broadcastProjectAssignmentsChanged($assignment->project_id);
    }

    public function deleteFullPeriodAssignment(
        int $projectId,
        string $employableType,
        int $employableId,
        string $groupId
    ): bool {
        $rows = ProjectDayAssignment::withTrashed()
            ->where('project_id', $projectId)
            ->forEmployable($employableType, $employableId)
            ->where('group_id', $groupId)
            ->where('is_full_period', true)
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        DB::transaction(static function () use ($rows): void {
            ProjectDayAssignment::withTrashed()->whereIn('id', $rows->pluck('id'))->forceDelete();
        });
        $this->broadcastProjectAssignmentsChanged($projectId);

        if ($rows->contains(static fn (ProjectDayAssignment $row) => $row->isBinding())) {
            $this->notifyPersonAboutBindingChange(
                'removed',
                Project::withTrashed()->find($projectId),
                $employableType,
                $employableId,
                $this->formatDateSpanLabel($rows->min('date'), $rows->max('date'))
            );
        }

        return true;
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

        // Tage mit bestehender Schicht desselben Projekts werden absorbiert statt
        // konvertiert (Parität zu createAssignments: dort werden sie übersprungen)
        $shiftCoveredDates = $this->getShiftCoveredDates(
            $assignment->project_id,
            $assignment->employable_type,
            $assignment->employable_id,
            $wishRows->min('date')->format('Y-m-d'),
            $wishRows->max('date')->format('Y-m-d')
        );

        DB::transaction(static function () use ($wishRows, $existingBindingDates, $shiftCoveredDates): void {
            foreach ($wishRows as $row) {
                $date = $row->date->format('Y-m-d');

                if ($existingBindingDates->contains($date) || $shiftCoveredDates->contains($date)) {
                    $row->forceDelete();

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

        $this->notifyPersonAboutBindingChange(
            'wish_accepted',
            $assignment->project,
            $assignment->employable_type,
            $assignment->employable_id,
            $this->formatDateSpanLabel($wishRows->min('date'), $wishRows->max('date'))
        );

        $this->broadcastProjectAssignmentsChanged($assignment->project_id);
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

        DB::transaction(static function () use ($assignments, $shift): void {
            foreach ($assignments as $assignment) {
                $assignment->update(['superseded_by_shift_id' => $shift->id]);
                $assignment->delete();
            }
        });

        if ($assignments->isNotEmpty()) {
            $this->broadcastProjectAssignmentsChanged($projectId);
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
        $dateStrings = $supersededRows->pluck('date')->map(static fn ($date) => $date->format('Y-m-d'));

        $coveringShiftIdsByDate = $this->getCoveringShiftIdsByDate(
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
            ->whereIn('date', $dateStrings)
            ->get();

        // Zwischenzeitliche Frei-/Abwesenheits-Einträge: dort nicht restaurieren
        // (Frei löst verbindliche Zuordnungen und Wünsche auf, Abwesenheit nur Wünsche —
        // gleiche Invariante wie handleVacationEntry)
        $vacationTypesByDate = \Artwork\Modules\Vacation\Models\Vacation::query()
            ->without(['series', 'conflicts'])
            ->where('vacationer_type', $employableType)
            ->where('vacationer_id', $employableId)
            ->whereIn('date', $dateStrings)
            ->whereIn('type', ['FREE_WORK', 'OFF_WORK', 'NOT_AVAILABLE'])
            ->get(['date', 'type'])
            ->groupBy(static fn ($vacation) => Carbon::parse($vacation->date)->format('Y-m-d'));

        foreach ($supersededRows as $row) {
            $date = $row->date->format('Y-m-d');

            $hasActiveDuplicate = $activeRows->contains(
                static fn (ProjectDayAssignment $active) => $active->date->format('Y-m-d') === $date
                    && $active->type === $row->type
            );

            if ($hasActiveDuplicate) {
                $row->update(['superseded_by_shift_id' => null]);

                continue;
            }

            if ($coveringShiftIdsByDate->has($date)) {
                // Verweis auf die noch abdeckende Schicht umhängen — sonst findet deren
                // späterer Entzug die Zeile nicht mehr und die Zuordnung geht verloren
                $row->update(['superseded_by_shift_id' => $coveringShiftIdsByDate->get($date)]);

                continue;
            }

            $dayVacations = $vacationTypesByDate->get($date);
            $dissolvedByVacation = $dayVacations !== null && (
                $dayVacations->contains(static fn ($vacation) => $vacation->type === 'FREE_WORK')
                || !$row->isBinding()
            );

            if ($dissolvedByVacation) {
                $row->update(['superseded_by_shift_id' => null]);

                continue;
            }

            $row->restore();
            $row->update(['superseded_by_shift_id' => null]);
        }

        $this->broadcastProjectAssignmentsChanged($projectId);
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

        foreach ($assignments->pluck('project_id')->unique() as $projectId) {
            $this->broadcastProjectAssignmentsChanged((int) $projectId);
        }

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
        return $this->getCoveringShiftIdsByDate(
            $projectId,
            $employableType,
            $employableId,
            $rangeStart,
            $rangeEnd,
            $excludeShiftId
        )->keys()->values();
    }

    /**
     * Wie getShiftCoveredDates, aber als Map Y-m-d => abdeckende shift_id —
     * damit beim Schicht-Entzug der superseded_by_shift_id-Verweis auf eine
     * noch abdeckende Schicht umgehängt werden kann.
     *
     * @return SupportCollection<string, int>
     */
    private function getCoveringShiftIdsByDate(
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
                $key = $day->format('Y-m-d');

                if (!$covered->has($key)) {
                    $covered->put($key, (int) $shift->id);
                }
            }
        }

        return $covered;
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
     * Benachrichtigt die betroffene Person über Anlegen/Entfernen einer verbindlichen
     * Zuordnung bzw. die Übernahme ihres Wunsches — pro Tag gebündelt: existiert für
     * heute bereits eine ungelesene Benachrichtigung derselben Art, wird sie zu
     * „:count neue Projektzuordnungen" zusammengefasst statt eine weitere zu erzeugen
     * (Produktentscheidung 21.07.2026). Nur User erhalten Benachrichtigungen; die
     * auslösende Person selbst nie.
     */
    private function notifyPersonAboutBindingChange(
        string $kind,
        ?Project $project,
        string $employableType,
        int $employableId,
        string $datesLabel,
        ?string $reasonKey = null
    ): void {
        if ($employableType !== User::class || $employableId === Auth::id()) {
            return;
        }

        $person = User::find($employableId);

        if ($person === null) {
            return;
        }

        [$singleKey, $bundledKey, $icon, $priority, $broadcastType] = match ($kind) {
            'assigned' => [
                'notification.project_assignment.person_assigned',
                'notification.project_assignment.person_assigned_bundled',
                'green',
                3,
                'success',
            ],
            'removed' => [
                'notification.project_assignment.person_removed',
                'notification.project_assignment.person_removed_bundled',
                'red',
                2,
                'error',
            ],
            'wish_accepted' => [
                'notification.project_assignment.person_wish_accepted',
                'notification.project_assignment.person_wish_accepted_bundled',
                'green',
                3,
                'success',
            ],
        };

        $descriptionLine = trim(($project?->name ?? '') . ' · ' . $datesLabel, ' ·');

        if ($reasonKey !== null) {
            $descriptionLine .= ' · ' . __($reasonKey, [], $person->language);
        }

        $notificationKey = sprintf(
            'project-assignment-%s-%d-%s',
            $kind,
            $person->id,
            Carbon::now()->format('Y-m-d')
        );

        $existing = DatabaseNotification::query()
            ->where('notifiable_type', $person->getMorphClass())
            ->where('notifiable_id', $person->id)
            ->whereNull('read_at')
            ->whereJsonContains('data->notificationKey', $notificationKey)
            ->first();

        if ($existing !== null) {
            $data = $existing->data;
            $bundleCount = ($data['bundleCount'] ?? 1) + 1;
            $data['bundleCount'] = $bundleCount;
            $data['title'] = __($bundledKey, ['count' => $bundleCount], $person->language);
            $descriptions = is_array($data['description'] ?? null) ? $data['description'] : [];
            $descriptions[] = ['type' => 'string', 'title' => $descriptionLine, 'href' => null];
            $data['description'] = array_values($descriptions);
            $data['created_at'] = Carbon::now()->translatedFormat('d.m.Y H:i');

            // created_at mit anheben, damit die gebündelte Benachrichtigung wieder oben
            // in der Liste auftaucht; bewusst KEIN erneuter Push — Sinn der Bündelung
            $existing->forceFill(['data' => $data, 'created_at' => Carbon::now()])->save();
            $person->update(['show_notification_indicator' => true]);

            return;
        }

        $title = __($singleKey, ['projectName' => $project?->name ?? ''], $person->language);

        $this->notificationService->setNotificationTo($person);
        $this->notificationService->setTitle($title);
        $this->notificationService->setIcon($icon);
        $this->notificationService->setPriority($priority);
        $this->notificationService->setProjectId($project?->id);
        $this->notificationService->setNotificationKey($notificationKey);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage([
            'id' => Str::uuid()->toString(),
            'type' => $broadcastType,
            'message' => $title,
        ]);
        $this->notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => $descriptionLine,
                'href' => null,
            ],
        ]);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }

    /**
     * Tage im Bereich, an denen ein aktueller Frei-/Abwesenheits-Eintrag der Person
     * eine Zuordnung des Typs auflösen würde — gleiche Invariante wie
     * handleVacationEntry/restoreForShiftRemoval: „Frei" (FREE_WORK) löst verbindliche
     * Zuordnungen und Wünsche auf, Abwesenheiten (OFF_WORK, NOT_AVAILABLE) nur Wünsche.
     *
     * @return SupportCollection<int, string> Y-m-d
     */
    private function getVacationBlockedDates(
        string $employableType,
        int $employableId,
        string $rangeStart,
        string $rangeEnd,
        bool $forBinding
    ): SupportCollection {
        return \Artwork\Modules\Vacation\Models\Vacation::query()
            ->without(['series', 'conflicts'])
            ->where('vacationer_type', $employableType)
            ->where('vacationer_id', $employableId)
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->whereIn('type', $forBinding ? ['FREE_WORK'] : ['FREE_WORK', 'OFF_WORK', 'NOT_AVAILABLE'])
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->values();
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
            ProjectDayAssignment::query()
                ->where('project_id', $project->id)
                ->delete();
            ProjectDayAssignment::onlyTrashed()
                ->where('project_id', $project->id)
                ->whereNotNull('superseded_by_shift_id')
                ->update(['superseded_by_shift_id' => null]);

            $this->broadcastProjectAssignmentsChanged($project->id);

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
                    ->where('date', '<', $period['start']->toDateString())
                    ->orWhere('date', '>', $period['end']->toDateString());
            })
            ->get();

        foreach ($outOfPeriodSingles->groupBy(static fn ($row) => $row->employable_type . '_' . $row->employable_id) as $rows) {
            $first = $rows->first();
            $workerName = $this->getWorkerName($first->employable_type, $first->employable_id);
            $datesLabel = $rows->map(static fn ($row) => $row->date->format('d.m.Y'))->unique()->implode(', ');

            ProjectDayAssignment::query()->whereIn('id', $rows->pluck('id'))->delete();

            $bindingRows = $rows->filter(static fn (ProjectDayAssignment $row) => $row->isBinding());

            if ($bindingRows->isNotEmpty()) {
                $this->notifyPlannersAboutDissolution($project, $workerName, $datesLabel, 'reschedule');
                $this->logProjectChange(
                    $project,
                    'Project assignment was dissolved because the project was rescheduled',
                    [$workerName, $datesLabel]
                );

                $this->notifyPersonAboutBindingChange(
                    'removed',
                    $project,
                    $bindingRows->first()->employable_type,
                    $bindingRows->first()->employable_id,
                    $bindingRows
                        ->map(static fn (ProjectDayAssignment $row) => $row->date->format('d.m.Y'))
                        ->unique()
                        ->implode(', '),
                    'notification.project_assignment.removed_reason_reschedule'
                );
            }

            $dissolvedRows[] = [
                'worker_name' => $workerName,
                'dates' => $datesLabel,
            ];
        }

        // Ganz-Zeitraum-Gruppen auf den neuen Zeitraum re-materialisieren
        $rematerialized = 0;

        // Gleicher Guard wie createFullPeriodAssignments: ein kaputter Termin (z. B.
        // Tippfehler-Jahr) dehnt den Zeitraum — ohne Cap würden hier synchron im
        // Event-Save-Request tausende Zeilen pro Gruppe materialisiert
        if ($period['start']->diffInDays($period['end']) > self::MAX_FULL_PERIOD_DAYS) {
            return ['dissolved' => $dissolvedRows, 'rematerialized' => 0];
        }

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

            // Von Schichten supersedete Tage der Gruppe gelten nicht als fehlend —
            // die Zuordnung ist dort durch die Schicht konkretisiert (Supersede-Design)
            $supersededDates = ProjectDayAssignment::onlyTrashed()
                ->where('group_id', $groupId)
                ->whereNotNull('superseded_by_shift_id')
                ->pluck('date')
                ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

            // Aktive Zeilen anderer Gruppen (gleiches Projekt/Person/Typ, z. B. Einzeltage)
            // dürfen nicht dupliziert werden
            $otherGroupDates = ProjectDayAssignment::query()
                ->where('project_id', $first->project_id)
                ->forEmployable($first->employable_type, $first->employable_id)
                ->where('type', $first->type)
                ->where('group_id', '!=', $groupId)
                ->whereBetween('date', [$targetDates->first(), $targetDates->last()])
                ->pluck('date')
                ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

            // Verbindliche: Tage mit bestehender Schicht desselben Projekts überspringen
            // (Parität zu createAssignments)
            $shiftCoveredDates = $first->isBinding()
                ? $this->getShiftCoveredDates(
                    $first->project_id,
                    $first->employable_type,
                    $first->employable_id,
                    $targetDates->first(),
                    $targetDates->last()
                )
                : collect();

            // Per Frei-/Abwesenheits-Eintrag aufgelöste Tage bleiben aufgelöst, solange
            // der Eintrag noch existiert — wird er gelöscht, kommt der Tag beim nächsten
            // Zeitraum-Sync automatisch zurück (Produktentscheidung 21.07.2026)
            $vacationBlockedDates = $this->getVacationBlockedDates(
                $first->employable_type,
                $first->employable_id,
                $targetDates->first(),
                $targetDates->last(),
                $first->isBinding()
            );

            $missingDates = $targetDates
                ->reject(static fn ($date) => $existingDates->contains($date))
                ->reject(static fn ($date) => $supersededDates->contains($date))
                ->reject(static fn ($date) => $otherGroupDates->contains($date))
                ->reject(static fn ($date) => $shiftCoveredDates->contains($date))
                ->reject(static fn ($date) => $vacationBlockedDates->contains($date));
            $obsoleteRows = $rows->reject(static fn ($row) => $targetDates->contains($row->date->format('Y-m-d')));

            // Supersedete Zeilen außerhalb des neuen Zeitraums neutralisieren: ein späterer
            // Schicht-Entzug darf keine Zuordnung außerhalb des Projektzeitraums restaurieren
            ProjectDayAssignment::onlyTrashed()
                ->where('group_id', $groupId)
                ->whereNotNull('superseded_by_shift_id')
                ->where(static function ($query) use ($targetDates): void {
                    $query
                        ->where('date', '<', $targetDates->first())
                        ->orWhere('date', '>', $targetDates->last());
                })
                ->update(['superseded_by_shift_id' => null]);

            if ($missingDates->isEmpty() && $obsoleteRows->isEmpty()) {
                continue;
            }

            DB::transaction(static function () use ($first, $missingDates, $obsoleteRows, $groupId): void {
                if ($obsoleteRows->isNotEmpty()) {
                    ProjectDayAssignment::query()->whereIn('id', $obsoleteRows->pluck('id'))->delete();
                }

                if ($missingDates->isNotEmpty()) {
                    $timestamp = now();
                    ProjectDayAssignment::query()->insert($missingDates->map(static fn (string $date) => [
                        'project_id' => $first->project_id,
                        'employable_type' => $first->employable_type,
                        'employable_id' => $first->employable_id,
                        'date' => $date,
                        'type' => $first->type,
                        'group_id' => $groupId,
                        'is_full_period' => true,
                        'created_by' => $first->created_by,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])->all());
                }
            });

            $rematerialized++;
        }

        if ($dissolvedRows !== [] || $rematerialized > 0) {
            $this->broadcastProjectAssignmentsChanged($project->id);
        }

        return ['dissolved' => $dissolvedRows, 'rematerialized' => $rematerialized];
    }

    private function broadcastProjectAssignmentsChanged(int $projectId): void
    {
        $broadcast = static fn (): mixed => broadcast(new ProjectDayAssignmentsChanged($projectId));

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($broadcast);

            return;
        }

        $broadcast();
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
     * @return array{start: Carbon, end: Carbon}|null
     */
    public function computePeriodWithoutEvent(
        Project $project,
        \Artwork\Modules\Event\Models\Event $event
    ): ?array {
        $periodFor = static function (bool $relevantOnly) use ($project, $event): ?array {
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
                ? [
                    'start' => Carbon::parse($row->min_start)->startOfDay(),
                    'end' => Carbon::parse($row->max_end)->startOfDay(),
                ]
                : null;
        };

        return $periodFor(true) ?? $periodFor(false);
    }

    /**
     * Einzeltag-Zuordnungen, die außerhalb des (hypothetischen) Zeitraums liegen —
     * pro Person gruppiert, für die Anzeige im Confirm-Dialog.
     *
     * @param array{start: Carbon, end: Carbon}|null $period
     * @return array<int, array{worker_name: string, type: string, dates: array<string>}>
     */
    public function getOutOfPeriodSingleDayAssignments(Project $project, ?array $period): array
    {
        $rows = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->where('is_full_period', false)
            ->when($period !== null, static function ($query) use ($period): void {
                $query->where(static function ($dateQuery) use ($period): void {
                    $dateQuery
                        ->where('date', '<', $period['start']->toDateString())
                        ->orWhere('date', '>', $period['end']->toDateString());
                });
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

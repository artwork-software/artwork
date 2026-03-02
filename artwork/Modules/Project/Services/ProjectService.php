<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectSortEnum;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserProjectManagementSettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly EventService $eventService,
        private readonly UserService $userService,
        private readonly CarbonService $carbonService,

    ) {
    }

    public function isManagerForProject(User $user, Project $project): bool
    {
        return $this->projectRepository->findManagers($project)->contains($user);
    }

    public function getProjectByCostCenter(string $costCenter): Project|null
    {
        return $this->projectRepository->getProjectByCostCenter($costCenter);
    }

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function getProjects(
        string $search = '',
        ?ProjectSortEnum $sortEnum = null,
        ?IlluminateCollection $projectStateIds = null,
        ?IlluminateCollection $projectFilters = null,
    ): \Laravel\Scout\Builder|Builder {
        $useSort = !is_null($sortEnum);

        $builder = $this->projectRepository->getProjectQuery($search);

        $builderCallback = function (
            Builder $builder
        ) use (
            $search,
            $useSort,
            $sortEnum,
            $projectFilters,
            $projectStateIds
        ): Builder {
            return $builder->with([
                'access_budget' => function ($query): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo', 'vacations']);
                },
                'categories',
                'genres',
                'sectors',
                'costCenter',
                'groups',
                'managerUsers' => function ($query): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo', 'vacations']);
                },
                'users' => function ($query): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo', 'vacations']);
                },
                'writeUsers' => function ($query): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo', 'vacations']);
                },
                'status',
                'delete_permission_users' => function ($query): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo', 'vacations']);
                }
            ])
                /** @todo für Jason:
                 * search muss raus wenn das mit Meilisearch klappt
                 */
                ->when(
                    strlen($search) > 0,
                    function (Builder $builder) use ($search): void {
                        $builder->where(function (Builder $query) use ($search): void {
                            $like = '%' . $search . '%';

                            $query
                                ->where('name', 'like', $like)
                                ->orWhere('artists', 'like', $like);
                        });
                    }
                )
                ->when(
                    $useSort,
                    function (Builder $builder) use ($sortEnum): void {
                        switch ($sortEnum) {
                            case ProjectSortEnum::ALPHABETICALLY_ASCENDING:
                            case ProjectSortEnum::ALPHABETICALLY_DESCENDING:
                                $builder->orderBy($sortEnum->mapToColumn(), $sortEnum->mapToDirection());
                                break;
                            case ProjectSortEnum::CHRONOLOGICALLY_ASCENDING:
                            case ProjectSortEnum::CHRONOLOGICALLY_DESCENDING:
                                $columns = $sortEnum->mapToColumn();
                                $dir = $sortEnum->mapToDirection();

                                $builder->orderBy(
                                    //order by no. 1: start time (get always first event)
                                    $this->eventService->getOrderBySubQueryBuilder($columns[0], 'asc'),
                                    $dir
                                );
                                $builder->orderBy(
                                    //order by no. 2: end time (get always latest event)
                                    $this->eventService->getOrderBySubQueryBuilder($columns[1], 'desc'),
                                    $dir
                                );
                                break;
                        }
                    }
                )
                ->when(
                    !$useSort,
                    function (Builder $builder): void {
                        $builder->orderBy('id', 'DESC');
                    }
                )
                ->when(
                    $projectStateIds?->isNotEmpty(),
                    function (Builder $builder) use ($projectStateIds): void {
                        $builder->whereIn('state', $projectStateIds);
                    }
                )
                // Apply "Show only my projects" filter separately with AND logic
                ->when(
                    $projectFilters?->contains('showOnlyMyProjects'),
                    function (Builder $builder): void {
                        $userId = $this->userService->getAuthUserId();
                        // Only show projects where the auth user is part of the project team.
                        // The creator (user_id) must be ignored completely for this filter.
                        $builder->whereHas('users', function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                    }
                )
                // Apply project type filters (groups/non-groups)
                ->when(
                    $projectFilters?->isNotEmpty(),
                    function (Builder $builder) use ($projectFilters): void {
                        // Handle project type filters (groups/non-groups) with OR logic
                        $builder->where(function (Builder $builder) use ($projectFilters): void {
                            $hasProjectTypeFilter = false;

                            if ($projectFilters->contains('showProjectGroups')) {
                                $builder->orWhere('is_group', 1);
                                $hasProjectTypeFilter = true;
                            }

                            if ($projectFilters->contains('showProjects')) {
                                $builder->orWhere('is_group', 0);
                                $hasProjectTypeFilter = true;
                            }

                            // If neither project type filter is active, show both projects and project groups
                            if (!$hasProjectTypeFilter) {
                                $builder->whereRaw('1 = 1');
                            }
                        });

                        // Handle time filters with same logic as type filters:
                        // Only filter when at least one time filter is active
                        $hasExpiredFilter = $projectFilters->contains('showExpiredProjects');
                        $hasFutureFilter = $projectFilters->contains('showFutureProjects');

                        if ($hasExpiredFilter || $hasFutureFilter) {
                            $todayMidnight = $this->carbonService->getTodayMidnight();

                            $builder->where(function (Builder $builder) use ($hasExpiredFilter, $hasFutureFilter, $todayMidnight, $projectFilters): void {
                                if ($hasExpiredFilter && $hasFutureFilter) {
                                    // Both active: show all projects (no time restriction)
                                    $builder->whereRaw('1 = 1');
                                } elseif ($hasExpiredFilter) {
                                    // Only expired: show projects with past events
                                    $builder->whereHas('events', function (Builder $query) use ($todayMidnight): void {
                                        $query->where('start_time', '<', $todayMidnight);
                                    });
                                    if (!$projectFilters->contains('hideProjectsWithoutEvents')) {
                                        $builder->orWhereDoesntHave('events');
                                    }
                                } elseif ($hasFutureFilter) {
                                    // Only future: show projects with future events
                                    $builder->whereHas('events', function (Builder $query) use ($todayMidnight): void {
                                        $query->where(function (Builder $q) use ($todayMidnight): void {
                                            $q->where('start_time', '>=', $todayMidnight)
                                              ->orWhere('end_time', '>=', $todayMidnight);
                                        });
                                    });
                                    if (!$projectFilters->contains('hideProjectsWithoutEvents')) {
                                        $builder->orWhereDoesntHave('events');
                                    }
                                }
                            });
                        }

                        // Handle hideProjectsWithoutEvents filter - when checked, exclude projects without events
                        if ($projectFilters->contains('hideProjectsWithoutEvents')) {
                            // Only show projects that have at least one event
                            if (!$hasExpiredFilter && !$hasFutureFilter) {
                                $builder->whereHas('events');
                            }
                        }

                        // Handle showOnlyProjectsWithoutGroup filter - when true, exclude project groups and projects with assigned groups
                        if ($projectFilters->contains('showOnlyProjectsWithoutGroup')) {
                            $builder->where('is_group', 0) // Exclude project groups
                                   ->whereDoesntHave('groups'); // Exclude projects that have a group assigned
                        }
                    }
                )
                ->where(function (Builder $builder): void {
                    $builder->whereJsonDoesntContain('pinned_by_users', Auth::id())
                        ->orWhereNull('pinned_by_users');
                })
                ->without(['shiftRelevantEventTypes']);
        };

        /** @todo für Jason:
         * Das interne Scout Eloquent query wird hier richtig durch den Callback gejagt aber
         * die Models die am Ende von Meilisearch rauspurzeln ignorieren das order by
         * Meilisearch wird ausschließlich für die volltextsuche verwendet
         * diese wurde erstmal auf die inperformante like suche umgebaut
         *
         * Ergebnis ist, dass die Models einfach nicht sortiert sind, die Filterungen funktionieren aber
         */
//        if ($builder instanceof \Laravel\Scout\Builder) {
//            $builder = $builder->query(function ($query) use ($builderCallback) {
//                return $builderCallback($query);
//            });
//        } else {
//            $builder = $builderCallback($builder);
//        }

        return $builderCallback($builder);
    }

    public function paginateProjects(
        string $search = '',
        int $perPage = 10,
        ?ProjectSortEnum $sortEnum = null,
        ?IlluminateCollection $projectStateIds = null,
        ?IlluminateCollection $projectFilters = null,
    ): LengthAwarePaginator {
        $projectQuery = $this->getProjects(
            $search,
            $sortEnum,
            $projectStateIds,
            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
            $projectFilters->filter(fn($filter, $enabled) => $enabled)->keys()
        );
        return $projectQuery->paginate($perPage);
    }

    public function getProjectGroups(): Collection
    {
        return $this->projectRepository->getProjectGroups();
    }

    public function pin(Project $project): bool
    {
        $user = Auth::user();
        $pinnedByUsers = $project->pinned_by_users;

        if (is_null($pinnedByUsers)) {
            $pinnedByUsers = [];
        }
        if (in_array($user->id, $pinnedByUsers)) {
            $userIndex = array_search($user->id, $pinnedByUsers);
            array_splice($pinnedByUsers, $userIndex, 1);
        } else {
            $pinnedByUsers[] = $user->id;
        }
        return $project->update(['pinned_by_users' => $pinnedByUsers]);
    }

    public function getUsersForProject($project): Collection
    {
        return $this->projectRepository->findUsers($project);
    }

    public function findById(int $id): Project
    {
        return $this->projectRepository->findOrFail($id);
    }

    public function save(Project $project): Project
    {
        /** @var Project $project */
        $project = $this->projectRepository->save($project);

        return $project;
    }

    public function softDelete(
        Project $project,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        CommentService $commentService,
        ChecklistService $checklistService,
        ProjectFileService $projectFileService,
        EventService $eventService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
        TaskService $taskService
    ): bool {
        $projectFileService->deleteAll($project->project_files);
        $eventService->deleteAll(
            $project->events,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService,
            $projectTabService
        );
        $checklistService->deleteAll($project->checklists, $taskService);
        $projectFileService->deleteAll($project->project_files);
        $commentService->deleteAll($project->comments, $changeService);

        $table = $project->table;
        if ($table) {
            $mainPositions = $table->mainPositions()->get();
            foreach ($mainPositions as $mainPosition) {
                $subPositions = $mainPosition->subPositions()->get();
                foreach ($subPositions as $subPosition) {
                    $subPositionRows = $subPosition->subPositionRows()->get();
                    foreach ($subPositionRows as $subPositionRow) {
                        $cells = $subPositionRow->cells()->get();
                        $comments = $subPositionRow->comments()->get();
                        foreach ($comments as $comment) {
                            $comment->delete();
                        }
                        foreach ($cells as $cell) {
                            $cell->comments()->delete();
                            $cell->calculations()->delete();
                            $cell->delete();
                        }
                        $subPositionRow->delete();
                    }
                    $subPosition->verified()->delete();
                    $subPosition->subPositionSumDetails()->delete();
                    $subPosition->delete();
                }
                $mainPosition->verified()->delete();
                $mainPosition->mainPositionSumDetails()->delete();
                $mainPosition->delete();
            }
            $columns = $table->columns()->get();
            foreach ($columns as $column) {
                $budgetSumDetails = $column->budgetSumDetails()->get();
                foreach ($budgetSumDetails as $budgetSumDetail) {
                    $budgetSumDetail->comments()->delete();
                    $budgetSumDetail->delete();
                }
                $column->delete();
            }
            $table->delete();
        }

        $project->categories()->detach();

        $project->contracts()->delete();

        $this->updateShiftRelevanteEventTypesForSoftDelete($project, now());

        $this->updateShiftContact($project, now());

        $this->updateMoneySources($project, now());

        return $project->delete();
    }

    public function forceDelete(
        Project $project,
        CommentService $commentService,
        ChecklistService $checklistService,
        EventService $eventService,
        ProjectFileService $projectFileService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        TaskService $taskService
    ): bool {
        // detach the shift relevant event types from the pivot table
        $this->deleteShiftRelevanteEventTypes($project);

        // detach the shift contact users from the pivot table
        $this->deleteShiftContact($project);

        // detach the money sources from the pivot table
        $this->deleteMoneySources($project);

        $checkLists = Checklist::onlyTrashed()->where('project_id', $project->id)->get();

        // force delete the checklists and their tasks
        $checklistService->forceDeleteAll($checkLists, $taskService);

        // force delete the events and their shifts
        $eventService->forceDeleteAll(
            $project->events,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService
        );

        // force delete the project files
        $projectFileService->forceDeleteAll($project->project_files);

        // force delete the comments
        $comments = Comment::onlyTrashed()->where('project_id', $project->id)->get();
        $commentService->forceDeleteAll($comments);

        // Soft delete the budget with all its relations
        $table = $project->table;
        if ($table) {
            // Soft delete the budget
            $mainPositions = $table->mainPositions()->get();
            foreach ($mainPositions as $mainPosition) {
                $subPositions = $mainPosition->subPositions()->get();
                foreach ($subPositions as $subPosition) {
                    $subPositionRows = $subPosition->subPositionRows()->get();
                    foreach ($subPositionRows as $subPositionRow) {
                        $cells = $subPositionRow->cells()->get();
                        $comments = $subPositionRow->comments()->get();
                        foreach ($comments as $comment) {
                            $comment->forceDelete();
                        }
                        foreach ($cells as $cell) {
                            $cell->comments()->forceDelete();
                            $cell->calculations()->forceDelete();
                            $cell->forceDelete();
                        }
                        $subPositionRow->forceDelete();
                    }
                    $subPosition->verified()->forceDelete();
                    $subPosition->subPositionSumDetails()->forceDelete();
                    $subPosition->forceDelete();
                }
                $mainPosition->verified()->forceDelete();
                $mainPosition->mainPositionSumDetails()->forceDelete();
                $mainPosition->forceDelete();
            }
            $columns = $table->columns()->get();
            foreach ($columns as $column) {
                $budgetSumDetails = $column->budgetSumDetails()->get();
                foreach ($budgetSumDetails as $budgetSumDetail) {
                    $budgetSumDetail->comments()->forceDelete();
                    $budgetSumDetail->forceDelete();
                }
                $column->forceDelete();
            }
            $table->forceDelete();
        }

        // force delete the project
        return $project->forceDelete();
    }

    public function restore(
        Project $project,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        CommentService $commentService,
        ChecklistService $checklistService,
        ProjectFileService $projectFileService,
        EventService $eventService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        TaskService $taskService
    ): bool {
        // restore
        $project->restore();

        // restore events
        $eventService->restoreAll(
            $project->events()->with(['shifts'])->onlyTrashed()->get(),
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService
        );

        // restore checklists and their tasks
        $checklistService->restoreAll($project->checklists()->onlyTrashed()->get(), $taskService);

        $table = $project->table()->onlyTrashed()->first();
        $table->restore();
        if ($table) {
            $columns = $table->columns()->onlyTrashed()->get();
            foreach ($columns as $column) {
                $column->restore();
                $budgetSumDetails = $column->budgetSumDetails()->onlyTrashed()->get();
                foreach ($budgetSumDetails as $budgetSumDetail) {
                    $budgetSumDetail->comments()->restore();
                    $budgetSumDetail->restore();
                }
            }
            // Soft delete the budget
            $mainPositions = $table->mainPositions()->onlyTrashed()->get();
            foreach ($mainPositions as $mainPosition) {
                $mainPosition->restore();
                $subPositions = $mainPosition->subPositions()->onlyTrashed()->get();
                foreach ($subPositions as $subPosition) {
                    $subPosition->restore();
                    $subPositionRows = $subPosition->subPositionRows()->onlyTrashed()->get();
                    foreach ($subPositionRows as $subPositionRow) {
                        $subPositionRow->restore();
                        $cells = $subPositionRow->cells()->onlyTrashed()->get();
                        $comments = $subPositionRow->comments()->onlyTrashed()->get();
                        foreach ($comments as $comment) {
                            $comment->restore();
                        }
                        foreach ($cells as $cell) {
                            $cell->restore();
                            $cell->comments()->restore();
                            $cell->calculations()->restore();
                        }
                    }
                    $subPosition->verified()->restore();
                    $subPosition->subPositionSumDetails()->restore();
                }
                $mainPosition->verified()->restore();
                $mainPosition->mainPositionSumDetails()->restore();
            }
        }

        // soft delete the comments
        $commentService->restoreAll($project->comments()->get());
        //$project->comments()->restore();

        // restore the project files
        $projectFileService->restoreAll($project->project_files()->get());

        // restore the contracts
        $project->contracts()->restore();

        // restore the shift relevant event types form the pivot table
        // (project_shift_relevant_event_types) use the deleted_at column without
        // detaching because we need it to restore the project

        $this->updateShiftRelevanteEventTypesForSoftDelete($project, null);

        $this->updateShiftContact($project, null);

        $this->updateMoneySources($project, null);


        return true;
    }

    public function getAll(array $with = []): Collection
    {
        return $this->projectRepository->getAll($with);
    }

    public function getByName(string $query): Collection
    {
        return $this->projectRepository->getByName($query);
    }

    public function getNonProjectGroupByName(string $query): ?Project
    {
        return $this->projectRepository->getByName($query)->where('is_group', '=', false)->first();
    }

    public function getProjectGroupByName(string $name): ?Project
    {
        return $this->projectRepository
            ->getByName($name)
            ->where('is_group', '=', true)
            ->first();
    }

    public function updateShiftContact(Project $project, $time): void
    {
        $project->shift_contact()
            ->updateExistingPivot(
                $project->shift_contact()
                    ->get()
                    ->pluck('id')
                    ->toArray(),
                ['deleted_at' => $time]
            );
    }

    private function updateShiftRelevanteEventTypesForSoftDelete(Project $project, $time): void
    {
        $project->shiftRelevantEventTypes()
            ->updateExistingPivot(
                $project->shiftRelevantEventTypes()
                    ->get()
                    ->pluck('id')
                    ->toArray(),
                ['deleted_at' => $time]
            );
    }

    private function deleteShiftRelevanteEventTypes(Project $project): void
    {
        $project->shiftRelevantEventTypes()->detach();
    }

    private function deleteShiftContact(Project $project): void
    {
        $project->shift_contact()->detach();
    }

    private function updateMoneySources(Project $project, $time): void
    {
        $project->moneySources()
            ->updateExistingPivot(
                $project->moneySources()
                    ->get()
                    ->pluck('id')
                    ->toArray(),
                ['deleted_at' => $time]
            );
    }

    private function deleteMoneySources(Project $project): void
    {
        $project->moneySources()->detach();
    }

    /**
     * @return array<string, mixed>
     * @throws ModelNotFoundException
     */
    public function getEventsWithRelevantShifts(int|Project $project): array
    {
        if (!$project instanceof Project) {
            $project = $this->projectRepository->findOrFail($project);
        }

        $eventsWithRelevant = [];
        foreach (
            $project
                ->events()
                ->whereIn('event_type_id', $project->shiftRelevantEventTypes->pluck('id'))
                ->with(['timelines', 'shifts', 'event_type'])
                ->orderBy('start_time', 'asc')
                ->get() as $event
        ) {
            $timeline = $event->timelines()
                ->orderBy('start_date')
                ->orderBy('start')
                ->orderBy('end_date')
                ->orderBy('end')
                ->get()
                ->toArray();

            foreach ($timeline as &$singleTimeLine) {
                $singleTimeLine['description_without_html'] = strip_tags($singleTimeLine['description']);
            }

            foreach ($event->shifts as $shift) {
                // Eager Load: Schicht- und Personen-bezogene Relationen, damit
                // die zugewiesenen Personen ihre globalen Qualifikationen im Payload enthalten
                $shift->load([
                    'shiftsQualifications',
                    // Personen inkl. globaler Qualifikationen
                    'users.globalQualifications',
                    'freelancer.globalQualifications',
                    'serviceProvider.globalQualifications',
                ]);

                foreach ($shift->users as $user) {
                    $user->formatted_vacation_days = $user->getFormattedVacationDays();
                }
            }


            $eventsWithRelevant[] = [
                'event' => $event,
                'timeline' => $timeline,
                'shifts' => $event->shifts,
                'event_type' => $event->event_type,
                'room' => $event->room()->without(['creator', 'admins'])->first()
            ];
        }
        return $this->sortEventsWithRelevant($eventsWithRelevant);
    }

    /**
     * @param array $eventsWithRelevant
     * @return array<string, mixed>
     */
    private function sortEventsWithRelevant(array $eventsWithRelevant): array
    {
        $userSortType = $this->userService->getAuthUser()->sort_type_shift_tab;

        if ($userSortType === null) {
            return $eventsWithRelevant;
        }

        usort($eventsWithRelevant, function ($a, $b) use ($userSortType) {
            $roomNameA = $a['room']['name'] ?? '';
            $roomNameB = $b['room']['name'] ?? '';
            if ($userSortType === 'ROOM_NAME_DESC') {
                $roomComparison = strcmp($roomNameB, $roomNameA);
            } else {
                $roomComparison = strcmp($roomNameA, $roomNameB);
            }
            if ($roomComparison !== 0) {
                return $roomComparison;
            }
            $dateA = $a['event']['event_date_without_time']['start_clear'] ?? '';
            $dateB = $b['event']['event_date_without_time']['start_clear'] ?? '';

            $timestampA = strtotime($dateA);
            $timestampB = strtotime($dateB);

            return $timestampA <=> $timestampB;
        });

        return $eventsWithRelevant;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function getFirstEventInProject(int|Project $project): Event|null
    {
        return $this->projectRepository->getFirstEvent($project);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function getLastEventInProject(int|Project $project): Event|null
    {
        return $this->projectRepository->getLastEvent($project);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function getLatestEndingEventInProject(int|Project $project): Event|null
    {
        return $this->projectRepository->getLatestEndingEvent($project);
    }

    public function getProjectsWithAccessBudgetAndManagerUsers(): Collection
    {
        return $this->projectRepository->getProjects(['access_budget', 'managerUsers']);
    }

    public function associateProjectWithGroup(Project $project, Project $projectGroup): void
    {
        $project->groups()->attach($projectGroup->id);
        $project->save();

        // Update is_group flag of the project group to true
        if (!$projectGroup->is_group) {
            $projectGroup->is_group = true;
            $projectGroup->save();
        }

        // Ensure the project has at least one column marked as relevant for project groups
        $table = $project->table()->first();
        if ($table) {
            $columns = $table->columns()->get();
            if ($columns->where('relevant_for_project_groups', true)->isEmpty()) {
                // Mark the last column as relevant for project groups
                $lastColumn = $columns->sortBy('position')->last();
                if ($lastColumn) {
                    $lastColumn->update(['relevant_for_project_groups' => true]);
                }
            }
        }
    }

    public function scoutSearch(string $query): \Laravel\Scout\Builder
    {
        return $this->projectRepository->scoutSearch($query);
    }

    public function pinnedProjects(int $userId): Collection
    {
        return $this->projectRepository->pinnedProjects($userId);
    }

    public function attachManagementUsersWithoutSelf(Project $project, IlluminateCollection $userIds, int $authId): void
    {
        $usersToAttach = $userIds->filter(fn($user) => $user !== $authId)
            ->mapWithKeys(fn($user) => [$user => [
                'access_budget' => false,
                'is_manager' => true,
                'can_write' => false,
                'delete_permission' => false
            ]]);

        $project->users()->attach($usersToAttach);
    }

    public function attachManagementUsers(Project $project, array $userIds): void
    {
        $usersToAttach = collect($userIds)
            ->mapWithKeys(fn($user) => [$user => [
                'access_budget' => false,
                'is_manager' => true,
                'can_write' => false,
                'delete_permission' => false
            ]]);

        $project->users()->attach($usersToAttach);
    }

    public function attachUserToProject(Project $project, int $userId, bool $isManager): void
    {
        $project->users()->attach($userId, [
            'access_budget' => false,
            'is_manager' => $isManager,
            'can_write' => false,
            'delete_permission' => false
        ]);
    }

    public function syncCategories(Project $project, IlluminateCollection $categories, ?int $mainCategoryId = null): void
    {
        $syncData = $this->buildSyncDataWithMain($categories, $mainCategoryId);
        $project->categories()->sync($syncData);
    }

    public function syncGenres(Project $project, IlluminateCollection $genres, ?int $mainGenreId = null): void
    {
        $syncData = $this->buildSyncDataWithMain($genres, $mainGenreId);
        $project->genres()->sync($syncData);
    }

    public function syncSectors(Project $project, IlluminateCollection $sectors, ?int $mainSectorId = null): void
    {
        $syncData = $this->buildSyncDataWithMain($sectors, $mainSectorId);
        $project->sectors()->sync($syncData);
    }

    private function buildSyncDataWithMain(IlluminateCollection $ids, ?int $mainId): array
    {
        $syncData = [];
        foreach ($ids as $id) {
            $syncData[$id] = ['is_main' => $mainId !== null && (int) $id === $mainId];
        }
        return $syncData;
    }

    public function detachManagementUsers(Project $project, bool $detachingAll = false, array $userIds = []): void
    {
        if ($detachingAll) {
            $project->managerUsers()->detach();
        } else {
            $project->managerUsers()->detach($userIds);
        }
    }

    public function updateProject(Project $project, array $data): Project
    {
        $this->projectRepository->update($project, $data);
        return $project;
    }

    public function getMyLastProject(int $userId): ?Project
    {
        return $this->projectRepository->getMyLastProject($userId);
    }
}

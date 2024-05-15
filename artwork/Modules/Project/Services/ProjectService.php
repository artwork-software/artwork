<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventComment\Services\EventCommentService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\SubEvent\Services\SubEventService;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

readonly class ProjectService
{
    public function __construct(private ProjectRepository $projectRepository)
    {
    }

    public function isManagerForProject(User $user, Project $project): bool
    {
        return $this->projectRepository->findManagers($project)->contains($user);
    }

    public function getProjectByCostCenter(string $costCenter): Project|null
    {
        return $this->projectRepository->getProjectByCostCenter($costCenter);
    }

    public function pin(Project $project): bool
    {
        $user = Auth::user();
        $pinnedByUsers = $project->pinned_by_users;

        if (is_null($pinnedByUsers)) {
            $pinnedByUsers = [];
        }
        if (in_array($user->id, $pinnedByUsers)) {
            $pinnedByUsers = array_diff($pinnedByUsers, [$user->id]);
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

        // force delete the checklists and their tasks
        $checklistService->forceDeleteAll($project->checklists, $taskService);

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
        $commentService->forceDeleteAll($project->comments);

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

    public function getAll(): Collection
    {
        return $this->projectRepository->getAll();
    }

    public function getByName(string $query): Collection
    {
        return $this->projectRepository->getByName($query);
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
                ->with(['timelines', 'shifts', 'event_type', 'room'])
                ->get() as $event
        ) {
            $timeline = $event->timelines()->get()->toArray();

            foreach ($timeline as &$singleTimeLine) {
                $singleTimeLine['description_without_html'] = strip_tags($singleTimeLine['description']);
            }

            usort($timeline, function ($a, $b) {
                if ($a['start'] === null && $b['start'] === null) {
                    return 0;
                } elseif ($a['start'] === null) {
                    return 1; // $a should come later in the array
                } elseif ($b['start'] === null) {
                    return -1; // $b should come later in the array
                }

                // Compare the 'start' values for ascending order
                return strtotime($a['start']) - strtotime($b['start']);
            });


            foreach ($event->shifts as $shift) {
                $shift->load('shiftsQualifications');
            }


            $shiftsToReturn = $event->shifts->map(function ($shift) use ($event) {
                $shift->margin_top = $this->getMarginTopForShift($event, $shift);
                return $shift;
            });


            $eventsWithRelevant[$event->id] = [
                'event' => $event,
                'timeline' => $timeline,
                'shifts' => $shiftsToReturn,
                'event_type' => $event->event_type,
                'room' => $event->room,
            ];
        }
        rsort($eventsWithRelevant);

        return $eventsWithRelevant;
    }

    private function getMarginTopForShift(Event $event, Shift $shift): float
    {

        // get difference between $event->earliest_start_datetime and $shift->start_date + $shift->start
        $eventEarliestStartDateTime = Carbon::parse($event->earliest_start_datetime);
        $startDate = Carbon::parse($shift->start_date);
        $startTime = Carbon::parse($shift->start);
        $shiftStartDateTime = Carbon::parse($startDate->toDateString() . ' ' . $startTime->toTimeString());
        //$shiftStartDateTime = Carbon::parse($shift->start_date- . ' ' . $shift->start);

        // calculate the difference
        $diff = $eventEarliestStartDateTime->diffInMinutes($shiftStartDateTime);
        $pixelHeight = $diff / 60 * 180;

        // if the calculated height is null or 0 than return 36
        if ($pixelHeight === null || $pixelHeight === 0) {
            return 36;
        }

        if ($pixelHeight > 8640) {
            return 8640;
        }
        return $pixelHeight;
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

    public function getProjectsWithAccessBudgetAndManagerUsers(): Collection
    {
        return $this->projectRepository->getProjects(['access_budget', 'managerUsers']);
    }
}

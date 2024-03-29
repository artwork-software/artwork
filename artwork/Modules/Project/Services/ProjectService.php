<?php

namespace Artwork\Modules\Project\Services;

use App\Models\MoneySourceFile;
use App\Models\User;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly EventService $eventService,
        private readonly ProjectFileService $projectFileService,
        private readonly ChecklistService $checklistService,
        private readonly CommentService $commentService
    ) {
    }

    public function isManagerForProject(User $user, Project $project): bool
    {
        return $this->projectRepository->findManagers($project)->contains($user);
    }

    public function getProjectsByCostCenter(string $costCenter): Collection
    {
        return $this->projectRepository->getProjectsByCostCenter($costCenter);
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
        return $this->projectRepository->findById($id);
    }

    public function softDelete(Project $project): bool
    {
        // delete project files
        $this->projectFileService->deleteAll($project->project_files);

        // delete all events and their shifts
        $this->eventService->deleteAll($project->events);

        // delete all checklists and their tasks
        $this->checklistService->deleteAll($project->checklists);

        // Soft delete all project files
        $this->projectFileService->deleteAll($project->project_files);

        $this->commentService->deleteAll($project->comments);

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

        // soft delete the shift relevant event types form the pivot table
        $this->updateShiftRelevanteEventTypesForSoftDelete($project, now());

        // soft delete the shift contact users
        $this->updateShiftContact($project, now());

        // soft delete the money sources form money_source_project pivot table
        $this->updateMoneySources($project, now());

        return $project->delete();
    }

    public function forceDelete(Project $project): bool
    {
        // detach the shift relevant event types from the pivot table
        $this->deleteShiftRelevanteEventTypes($project);

        // detach the shift contact users from the pivot table
        $this->deleteShiftContact($project);

        // detach the money sources from the pivot table
        $this->deleteMoneySources($project);

        // force delete the checklists and their tasks
        $this->checklistService->forceDeleteAll($project->checklists);

        // force delete the events and their shifts
        $this->eventService->forceDeleteAll($project->events);

        // force delete the project files
        $this->projectFileService->forceDeleteAll($project->project_files);

        // force delete the comments
        $this->commentService->forceDeleteAll($project->comments);

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

    public function restore(Project $project): bool
    {
        // restore
        $project->restore();

        // restore events
        $this->eventService->restoreAll($project->events()->with(['shifts'])->onlyTrashed()->get());

        // restore checklists and their tasks
        $this->checklistService->restoreAll($project->checklists()->onlyTrashed()->get());

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
        $this->commentService->restoreAll($project->comments()->get());
        //$project->comments()->restore();

        // restore the project files
        $this->projectFileService->restoreAll($project->project_files()->get());

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

    public function getAll(): Collection
    {
        return $this->projectRepository->getAll();
    }
}

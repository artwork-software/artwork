<?php

namespace Artwork\Modules\Project\Services;

use App\Models\User;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
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
        $checklists = $project->checklists;

        foreach ($checklists as $checklist) {
            $checklist->tasks()->delete();
            $checklist->delete();
        }
        // Soft delete the shifts

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

        // soft delete the comments
        $project->comments()->delete();

        $project->project_files()->delete();

        return $project->delete();
    }

    public function forceDelete(Project $project): bool
    {
        $checklists = $project->checklists;

        foreach ($checklists as $checklist) {
            $checklist->tasks()->forceDelete();
            $checklist->forceDelete();
        }
        // Soft delete the shifts


        // Soft delete the budget
        $project->table()->forceDelete();

        // soft delete the comments
        $project->comments()->forceDelete();


        $project->project_files()->forceDelete();

        return $project->forceDelete();
    }

    public function restore(Project $project): bool
    {
        $checklists = $project->checklists()->onlyTrashed()->get();

        foreach ($checklists as $checklist) {
            $checklist->tasks()->restore();
            $checklist->restore();
        }
        // Soft delete the shifts

        // Soft delete the budget
        $project->table()->restore();

        // soft delete the comments
        $project->comments()->restore();

        $project->project_files()->restore();

        return $project->restore();
    }
}

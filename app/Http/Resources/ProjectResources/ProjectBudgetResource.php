<?php

namespace App\Http\Resources\ProjectResources;

use App\Http\Resources\ContractResource;
use App\Http\Resources\DepartmentIndexResource;
use App\Http\Resources\ProjectFileResource;
use App\Http\Resources\UserResourceWithoutShifts;
use Artwork\Modules\Project\Models\ProjectStates;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectBudgetResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $historyArray = [];
        $historyComplete = $this->historyChanges()->all();

        foreach ($historyComplete as $history) {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'isMemberOfADepartment' => $this->departments
                ->contains(fn ($department) => $department->users->contains(Auth::user())),
            'key_visual_path' => $this->key_visual_path,
            'cost_center' => $this->costCenter,
            'moneySources' => $this->moneySources,
            'project_files' => ProjectFileResource::collection($this->project_files),
            'contracts' => ContractResource::collection($this->contracts),
            'access_budget' => $this->access_budget,
            'state' => ProjectStates::find($this->state),
            'write_auth' => $this->writeUsers,
            'users' => UserResourceWithoutShifts::collection($this->users)->resolve(),
            //needed for ProjectShowHeaderComponent
            'project_history' => $historyArray,
            'delete_permission_users' => $this->delete_permission_users,
            'project_managers' => $this->managerUsers,
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'own_copyright' => $this->own_copyright,
            'live_music' => $this->live_music,
            'collecting_society' => $this->collectingSociety,
            'law_size' => $this->law_size,
            'cost_center_description' => $this->cost_center_description,
        ];
    }
}

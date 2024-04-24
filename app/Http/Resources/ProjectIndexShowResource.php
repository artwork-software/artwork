<?php

namespace App\Http\Resources;

use Artwork\Modules\Project\Models\ProjectStates;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProjectIndexShowResource extends JsonResource
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
            'number_of_participants' => $this->number_of_participants,
            'is_group' => $this->is_group,
            'group' => $this->groups,
            'sectors' => $this->sectors,
            'categories' => $this->categories,
            'genres' => $this->genres,
            'access_budget' => $this->access_budget,
            'project_managers' => $this->managerUsers,
            'write_auth' => $this->writeUsers,
            'delete_permission_users' => $this->delete_permission_users,
            'curr_user_is_related' => $this->users->contains(Auth::id()),
            'key_visual' => $this->key_visual_path,
            'cost_center' => $this->costCenter,
            'moneySources' => $this->money_sources,
            'users' => UserResourceWithoutShifts::collection($this->users)->resolve(),
            'project_history' => $historyArray,
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'state' => ProjectStates::find($this->state),
            'isMemberOfADepartment' => $this->departments
                ->contains(fn ($department) => $department->users->contains(Auth::user())),
            'budget_deadline' => $this->budget_deadline,
            'pinned_by_users' => $this->pinned_by_users,
            'first_tab_id' => ProjectTab::query()->first()->id
        ];
    }
}

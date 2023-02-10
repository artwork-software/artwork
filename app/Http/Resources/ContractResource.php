<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function getAccessibleUsers(): array|\JsonSerializable|\Illuminate\Contracts\Support\Arrayable
    {
        $usersWithAccess = [];
        $project = Project::where('id', $this->project_id)->with(['users'])->first();
        foreach($project->users as $user) {
            if($user->pivot->is_manager) {
                if(!in_array($user, $usersWithAccess)) {
                    $usersWithAccess[] = $user;
                }
            }
        }
        return $usersWithAccess;
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'basename' => $this->basename,
            'project' => $this->project,
            'amount' => $this->amount,
            'contract_type' => $this->contract_type,
            'company_type' => $this->company_type,
            'ksk_liable' => $this->ksk_liable,
            'partner' => $this->contract_partner,
            'resident_abroad' => $this->resident_abroad,
            'has_power_of_attorney' => $this->has_power_of_attorney,
            'currency' => $this->currency,
            'is_freed' => $this->is_freed,
            'description' => $this->description,
            'accessibleUsers' => UserIndexResource::collection($this->getAccessibleUsers())->resolve(),
            'tasks' => Task::where('contract_id', $this->id)->get()
        ];
    }
}

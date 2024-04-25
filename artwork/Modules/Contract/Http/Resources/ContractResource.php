<?php

namespace Artwork\Modules\Contract\Http\Resources;

use Artwork\Modules\Project\Http\Resources\CommentResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * @return User[]
     */
    public function getAccessibleUsers(): array
    {
        $usersWithAccess = $this->accessingUsers->all();
        $project = Project::where('id', $this->project_id)->with(['users'])->first();
        foreach ($project->users as $user) {
            if ($user->pivot->is_manager) {
                // check if user->id is already in $usersWithAccess
                $userExists = false;
                foreach ($usersWithAccess as $userWithAccess) {
                    if ($userWithAccess->id === $user->id) {
                        $userExists = true;
                    }
                }
                if (!$userExists) {
                    array_push($usersWithAccess, $user);
                }
            }
        }

        return $usersWithAccess;
    }

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'creator' => $this->creator,
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
            'tasks' => Task::where('contract_id', $this->id)->get(),
            'comments' => CommentResource::collection($this->comments)->resolve()
        ];
    }
}

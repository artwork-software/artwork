<?php

namespace Artwork\Modules\Contract\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Http\Resources\CommentResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAccessibleUsers(): \Illuminate\Support\Collection
    {
        $usersWithAccess = collect($this->accessingUsers->all());
        $project = Project::where('id', $this->project_id)->with(['users'])->first();

        foreach ($project->users as $user) {
            if ($user->pivot->is_manager && !$usersWithAccess->contains('id', $user->id)) {
                $usersWithAccess->push($user);
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
            'ksk_amount' => $this->ksk_amount,
            'ksk_reason' => $this->ksk_reason,
            'partner' => $this->contract_partner,
            'resident_abroad' => $this->resident_abroad,
            'foreign_tax' => $this->foreign_tax,
            'foreign_tax_amount' => $this->foreign_tax_amount,
            'foreign_tax_reason' => $this->foreign_tax_reason,
            'reverse_charge_amount' => $this->reverse_charge_amount,
            'deadline_date' => $this->deadline_date,
            'has_power_of_attorney' => $this->has_power_of_attorney,
            'currency' => $this->currency,
            'is_freed' => $this->is_freed,
            'description' => $this->description,
            'accessibleUsers' => $this->getAccessibleUsers()->map(fn ($user) => [
                'resource' => class_basename($user),
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'profile_photo_url' => $user->profile_photo_url,
                'email' => $user->email,
                'departments' => $user->departments,
                'position' => $user->position,
                'business' => $user->business,
                'phone_number' => $user->phone_number,
                'project_management' => $user->can(PermissionEnum::PROJECT_MANAGEMENT->value),
                'display_name' => $user->getDisplayNameAttribute(),
                'type' => $user->getTypeAttribute(),
                'assigned_craft_ids' => $user->getAssignedCraftIdsAttribute(),
            ]),
            //'accessibleUsers' => UserIndexResource::collection($this->getAccessibleUsers())->resolve(),
            'tasks' => Task::where('contract_id', $this->id)->get(),
            'comments' => CommentResource::collection($this->comments)->resolve()
        ];
    }
}

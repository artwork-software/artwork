<?php

namespace Artwork\Modules\Project\Http\Resources;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\ProjectStates;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Http\Resources\UserWithoutShiftsResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectShiftResource extends JsonResource
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
            'state' => ProjectStates::find($this->state),
            'write_auth' => $this->writeUsers,
            'users' => UserWithoutShiftsResource::collection($this->users)->resolve(),

            'shift_relevant_event_types' => $this->shiftRelevantEventTypes()->get(),
            'shift_contacts' => $this->shift_contact()->get(),
            'shiftDescription' => $this->shift_description,

            //needed for ProjectShowHeaderComponent
            'project_history' => $historyArray,
            'delete_permission_users' => $this->delete_permission_users,
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::without(['contacts'])->get(),
        ];
    }
}

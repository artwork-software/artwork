<?php

namespace Artwork\Modules\Project\Http\Resources;

use Artwork\Modules\Department\Http\Resources\DepartmentIndexResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectStates;
use Artwork\Modules\User\Http\Resources\UserWithoutShiftsResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Project
 */
class ProjectCommentResource extends JsonResource
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

            'write_auth' => $this->writeUsers,
            'users' => UserWithoutShiftsResource::collection($this->users)->resolve(),

            //needed for ProjectShowHeaderComponent
            'project_history' => $historyArray,
            'delete_permission_users' => $this->delete_permission_users,
            'project_managers' => $this->managerUsers,
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'state' => ProjectStates::find($this->state),
            'comments' => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'text' => $comment->text,
                'created_at' => $comment->created_at->format('d.m.Y, H:i'),
                'user' => $comment->user
            ]),
        ];
    }
}

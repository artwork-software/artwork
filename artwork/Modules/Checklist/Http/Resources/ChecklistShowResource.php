<?php

namespace Artwork\Modules\Checklist\Http\Resources;

use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'tasks' => $this->tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'done' => $task->done,
            ]),
            'users' => $this->users->map(fn (User $user) => [
                'id' => $user->id,
                'profile_photo_url' => $user->profile_photo_url,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ])
        ];
    }
}

<?php

namespace Artwork\Modules\ChecklistTemplate\Http\Resources;

use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistTemplateIndexResource extends JsonResource
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
            'user' => $this->user,
            'task_templates' => $this->task_templates->map(fn (TaskTemplate $taskTemplate) => [
                'id' => $taskTemplate->id,
                'name' => $taskTemplate->name,
                'description' => $taskTemplate->description,
                'done' => $taskTemplate->done,
            ]),
            'users' => $this->users->map(fn (User $user) => [
                'id' => $user->id,
                'profile_photo_url' => $user->profile_photo_url,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at->format('d.m.Y, H:i')
        ];
    }
}

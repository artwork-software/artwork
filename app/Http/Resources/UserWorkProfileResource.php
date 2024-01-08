<?php

namespace App\Http\Resources;

use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWorkProfileResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            'can_master' => $this->can_master,
            'can_work_shifts' => $this->can_work_shifts,
            'accessibleCrafts' => Craft::query()
                ->where('assignable_by_all', '=', true)
                ->get()
                ->merge($this->crafts)
                ->toArray(),
            'assignedCrafts' => $this->assigned_crafts,
            'assignableCrafts' => Craft::query()->get()->filter(
                fn($craft) => !$this->assigned_crafts->pluck('id')->contains($craft->id)
            )->toArray()
        ];
    }
}

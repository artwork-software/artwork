<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDropResource extends JsonResource
{

    /**
     * @var null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
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
            'can_master' => $this->can_master,
            'can_work_shifts' => $this->can_work_shifts,
            'assigned_crafts_ids' => $this->assigned_crafts->pluck('id'),
        ];
    }
}

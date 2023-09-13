<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @mixin \App\Models\User
 */
class UserDropResource extends JsonResource
{

    public static $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'can_master' => $this->can_master,
            'can_work_shifts' => $this->can_work_shifts,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderDropResource extends JsonResource
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
            'provider_name' => $this->provider_name,
            'profile_photo_url' => $this->profile_image,
            'assigned_crafts_ids' => $this->assigned_crafts->pluck('id'),
        ];
    }
}

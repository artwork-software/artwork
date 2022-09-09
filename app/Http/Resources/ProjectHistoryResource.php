<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProjectHistory
 */
class ProjectHistoryResource extends JsonResource
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
            'created_at' => $this->created_at->diffInHours() < 24
                ? $this->created_at->diffForHumans()
                : $this->created_at->format('d.m.Y, H:i'),
            'user' => $this->user,
            'description' => $this->description
        ];
    }
}

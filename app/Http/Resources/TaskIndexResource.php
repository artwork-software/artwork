<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Task
 */
class TaskIndexResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'deadline' => $this->deadline ? Carbon::parse($this->deadline)->format('d.m.Y, H:i') : null,
            'deadlineDate' => $this->deadline ? Carbon::parse($this->deadline)->format('Y-m-d') : null,
            'deadlineTime' => $this->deadline ? Carbon::parse($this->deadline)->format('H:i'): null,
            'deadline_dt_local' => $this->deadline ? Carbon::parse($this->deadline)->toDateTimeLocalString() : null,
            'order' => $this->order,
            'done' => $this->done,
            'done_by_user' => $this->user_who_done,
            'done_at' => $this->done_at?->format('d.m.Y, H:i'),
            'done_at_dt_local' => $this->done_at?->toDateTimeLocalString()
        ];
    }
}

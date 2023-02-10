<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CopyrightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'own_copyright' => $this->own_copyright,
            'live_music' => $this->live_music,
            'collecting_society' => $this->collecting_society,
            'law_size' => $this->law_size,
            'project_id' => $this->project_id
        ];
    }
}

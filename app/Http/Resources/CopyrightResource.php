<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopyrightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
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

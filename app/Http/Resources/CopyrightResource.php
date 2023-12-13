<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CopyrightResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'own_copyright' => $this->own_copyright,
            'live_music' => $this->live_music,
            'collecting_society' => $this->collectingSociety,
            'law_size' => $this->law_size,
            'project_id' => $this->project_id
        ];
    }
}

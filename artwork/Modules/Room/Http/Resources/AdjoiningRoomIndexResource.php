<?php

namespace Artwork\Modules\Room\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdjoiningRoomIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'end_date' => $this->end_date,
            'everyone_can_book' => $this->everyone_can_book,
            'order' => $this->order,
            'start_date' => $this->start_date,
            'temporary' => $this->temporary,
            "user_id" => $this->user_id,
            'area_id' => $this->area_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}

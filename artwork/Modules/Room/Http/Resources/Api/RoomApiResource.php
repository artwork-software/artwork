<?php

namespace Artwork\Modules\Room\Http\Resources\Api;

use Artwork\Modules\Room\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ein Raum in der Maschinen-API (GET /api/v1/rooms). Die ID ist dauerhaft und bleibt bei einer
 * Umbenennung gleich. Neue Felder nur ergänzen, nie umbenennen oder entfernen.
 *
 * @mixin \Artwork\Modules\Room\Models\Room
 */
class RoomApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'area_id' => $this->area?->id,
            'area_name' => $this->area?->name,
            'categories' => $this->categories
                ->map(static fn (RoomCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values()
                ->all(),
            'capacity' => $this->capacity,
            'color' => $this->getEffectiveColor(),
            'temporary' => (bool) $this->temporary,
            'temporary_from' => $this->temporary ? $this->start_date?->toDateString() : null,
            'temporary_until' => $this->temporary ? $this->end_date?->toDateString() : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

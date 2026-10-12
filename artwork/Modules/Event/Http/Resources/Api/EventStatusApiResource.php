<?php

namespace Artwork\Modules\Event\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ein Terminstatus in der Maschinen-API (GET /api/v1/event-statuses). Die ID ist dieselbe wie in
 * events[].status.id. Neue Felder nur ergänzen, nie umbenennen oder entfernen.
 *
 * @mixin \Artwork\Modules\Event\Models\EventStatus
 */
class EventStatusApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'order' => $this->order,
            'default' => (bool) $this->default,
        ];
    }
}

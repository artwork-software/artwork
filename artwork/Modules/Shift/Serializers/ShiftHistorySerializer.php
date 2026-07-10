<?php

namespace Artwork\Modules\Shift\Serializers;

use Artwork\Modules\Shift\Models\Shift;

readonly class ShiftHistorySerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serializeShift(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'craft_id' => $shift->craft_id,
            'start_date' => $shift->start_date?->format('d. M Y'),
            'end_date' => $shift->end_date?->format('d. M Y'),
            'start' => $shift->start,
            'end' => $shift->end,
            'description' => $shift->description,
            'is_committed' => (bool) $shift->is_committed,
            'in_workflow' => (bool) $shift->in_workflow,
            'deleted_at' => $shift->deleted_at?->toISOString(),
            'room' => $shift->room?->only(['id', 'name']),
            'project' => $shift->project?->only(['id', 'name']),
            'craft' => $shift->craft?->only(['id', 'name', 'abbreviation']),
        ];
    }
}

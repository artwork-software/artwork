<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/rooms/occupancy (core-api-events-rooms; Konzept B1 §3).
 */
class IndexAiRoomOccupancyRequest extends AiTimeRangeRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer'],
        ]);
    }
}

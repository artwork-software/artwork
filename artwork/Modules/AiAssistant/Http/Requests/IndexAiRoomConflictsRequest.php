<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/rooms/conflicts?from=&to=&room_id=&limit= (ai-scenarios-3 S6), hoechstens 92 Tage.
 */
class IndexAiRoomConflictsRequest extends IndexAiOpenShiftsRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->planningRangeRules(), [
            'room_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function roomId(): ?int
    {
        return $this->validated('room_id') === null ? null : (int) $this->validated('room_id');
    }
}

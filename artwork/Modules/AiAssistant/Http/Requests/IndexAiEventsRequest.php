<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/events (core-api-events-rooms; Konzept B1 §3).
 */
class IndexAiEventsRequest extends AiTimeRangeRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer'],
            'project_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}

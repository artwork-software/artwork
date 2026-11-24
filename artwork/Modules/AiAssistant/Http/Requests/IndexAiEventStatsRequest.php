<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * GET /api/v1/ai/events/stats (core-ai-event-stats-exports): Terminanzahl je Projekt, Raum oder
 * Terminart in einem Zeitraum. Gleiche Zeitraumregeln wie /ai/events (hoechstens 180 Tage).
 */
class IndexAiEventStatsRequest extends AiTimeRangeRequest
{
    public const GROUPS = ['project', 'room', 'type'];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'group_by' => ['required', 'string', Rule::in(self::GROUPS)],
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer'],
            'project_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'min_count' => ['nullable', 'integer', 'min:0'],
            'max_count' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function groupBy(): string
    {
        return (string) $this->validated('group_by');
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/rooms/free-slots (core-api-events-rooms; Konzept B1 §5).
 */
class IndexAiFreeSlotsRequest extends AiTimeRangeRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer'],
            'min_hours' => ['nullable', 'numeric', 'min:0.25', 'max:24'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            // "HH:MM-HH:MM", z.B. 18:00-23:00
            'day_window' => ['nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d-([01]\d|2[0-3]):[0-5]\d$/'],
        ]);
    }

    /**
     * @return array{from: string, to: string}
     */
    public function dayWindow(): array
    {
        $window = $this->validated('day_window');

        if (!is_string($window)) {
            return [
                'from' => (string) config('ai.day_window.from'),
                'to' => (string) config('ai.day_window.to'),
            ];
        }

        [$from, $to] = explode('-', $window);

        return ['from' => $from, 'to' => $to];
    }

    /**
     * @return list<int>
     */
    public function weekdays(): array
    {
        /** @var list<int|string> $weekdays */
        $weekdays = (array) $this->validated('weekdays', []);

        return array_values(array_map('intval', $weekdays));
    }

    public function minHours(): float
    {
        return (float) ($this->validated('min_hours') ?? 1.0);
    }
}

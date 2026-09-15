<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;

/**
 * POST /api/v1/ai/events/batch (core-write-endpoints-dry-run; Konzept W1 §1).
 *
 * Der Serienbeschrieb ist bewusst eng: Zeitraum, Wochentage, Uhrzeit, Dauer, Raum, Typ. Alles
 * andere legt der Bulk-Service der Oberflaeche fest.
 */
class StoreAiEventBatchRequest extends AiWriteRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'project_id' => ['required', 'integer'],
            'series_spec' => ['required', 'array'],
            'series_spec.from' => ['required', 'date'],
            'series_spec.to' => ['required', 'date', 'after_or_equal:series_spec.from'],
            'series_spec.weekdays' => ['nullable', 'array'],
            'series_spec.weekdays.*' => ['integer', 'between:1,7'],
            'series_spec.start_time' => ['required', 'date_format:H:i'],
            'series_spec.duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'series_spec.room_id' => ['required', 'integer'],
            'series_spec.type_id' => ['required', 'integer'],
            'series_spec.name' => ['nullable', 'string'],
            'series_spec.description' => ['nullable', 'string'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            $from = $this->input('series_spec.from');
            $to = $this->input('series_spec.to');

            if (!is_string($from) || !is_string($to)) {
                return;
            }

            $maxDays = (int) config('ai.max_range_days');

            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > $maxDays) {
                $validator->errors()->add(
                    'series_spec.to',
                    "Der Zeitraum darf hoechstens {$maxDays} Tage umfassen."
                );
            }
        });
    }

    /**
     * @return array{
     *     from: string, to: string, weekdays: list<int>, start_time: string,
     *     duration_minutes: int, room_id: int, type_id: int, name: string|null, description: string|null
     * }
     */
    public function seriesSpec(): array
    {
        /** @var array<string, mixed> $spec */
        $spec = (array) $this->validated('series_spec');

        /** @var list<int|string> $weekdays */
        $weekdays = (array) ($spec['weekdays'] ?? []);

        return [
            'from' => (string) $spec['from'],
            'to' => (string) $spec['to'],
            'weekdays' => array_values(array_map('intval', $weekdays)),
            'start_time' => (string) $spec['start_time'],
            'duration_minutes' => (int) $spec['duration_minutes'],
            'room_id' => (int) $spec['room_id'],
            'type_id' => (int) $spec['type_id'],
            'name' => isset($spec['name']) ? (string) $spec['name'] : null,
            'description' => isset($spec['description']) ? (string) $spec['description'] : null,
        ];
    }
}

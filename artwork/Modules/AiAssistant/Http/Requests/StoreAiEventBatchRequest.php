<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/events/batch (core-write-endpoints-dry-run; Konzept W1 §1).
 *
 * Der Serienbeschrieb ist bewusst eng: Zeitraum, Wochentage, Uhrzeit, Dauer, Raum, Typ. Alles
 * andere legt der Bulk-Service der Oberflaeche fest.
 *
 * `all_day` (core-api-lookups) schaltet Uhrzeit und Dauer ab: ganztaegige Termine haben keine,
 * und mit `required` waeren sie nur ueber eine erfundene Uhrzeit ausdrueckbar.
 *
 * core-ai-series-rooms (Feedback 17.11.): `project_id` ist optional — Termine ohne Projekt gibt es
 * in der Oberflaeche auch —, und `room_ids` legt dieselbe Serie in bis zu 10 Raeumen an. `room_id`
 * bleibt fuer bestehende Aufrufe gueltig.
 */
class StoreAiEventBatchRequest extends AiWriteRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'project_id' => ['nullable', 'integer'],
            'series_spec' => ['required', 'array'],
            'series_spec.from' => ['required', 'date'],
            'series_spec.to' => ['required', 'date', 'after_or_equal:series_spec.from'],
            'series_spec.weekdays' => ['nullable', 'array'],
            'series_spec.weekdays.*' => ['integer', 'between:1,7'],
            'series_spec.all_day' => ['nullable', 'boolean'],
            'series_spec.start_time' => [$this->unlessAllDay(), 'date_format:H:i'],
            'series_spec.duration_minutes' => [$this->unlessAllDay(), 'integer', 'min:15', 'max:1440'],
            'series_spec.room_id' => ['required_without:series_spec.room_ids', 'nullable', 'integer'],
            'series_spec.room_ids' => ['required_without:series_spec.room_id', 'nullable', 'array', 'min:1', 'max:10'],
            'series_spec.room_ids.*' => ['integer', 'distinct'],
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

    public function projectId(): ?int
    {
        $projectId = $this->validated('project_id');

        return $projectId === null ? null : (int) $projectId;
    }

    /**
     * `room_ids` und `room_id` zusammen, ohne Dubletten, in der Reihenfolge des Aufrufs.
     *
     * @param array<string, mixed> $spec
     * @return list<int>
     */
    private function roomIdsOf(array $spec): array
    {
        /** @var list<int|string> $ids */
        $ids = (array) ($spec['room_ids'] ?? []);

        if (isset($spec['room_id'])) {
            $ids[] = $spec['room_id'];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Pflicht, solange der Termin nicht ganztaegig ist.
     */
    private function unlessAllDay(): mixed
    {
        return Rule::requiredIf(fn (): bool => !$this->boolean('series_spec.all_day'));
    }

    /**
     * @return array{
     *     from: string, to: string, weekdays: list<int>, all_day: bool, start_time: string|null,
     *     duration_minutes: int|null, room_ids: list<int>, type_id: int, name: string|null,
     *     description: string|null
     * }
     */
    public function seriesSpec(): array
    {
        /** @var array<string, mixed> $spec */
        $spec = (array) $this->validated('series_spec');

        /** @var list<int|string> $weekdays */
        $weekdays = (array) ($spec['weekdays'] ?? []);

        $allDay = $this->boolean('series_spec.all_day');

        return [
            'from' => (string) $spec['from'],
            'to' => (string) $spec['to'],
            'weekdays' => array_values(array_map('intval', $weekdays)),
            'all_day' => $allDay,
            'start_time' => $allDay || !isset($spec['start_time']) ? null : (string) $spec['start_time'],
            'duration_minutes' => $allDay || !isset($spec['duration_minutes'])
                ? null
                : (int) $spec['duration_minutes'],
            'room_ids' => $this->roomIdsOf($spec),
            'type_id' => (int) $spec['type_id'],
            'name' => isset($spec['name']) ? (string) $spec['name'] : null,
            'description' => isset($spec['description']) ? (string) $spec['description'] : null,
        ];
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * PATCH /api/v1/ai/events/{event} (core-write-endpoints-dry-run; Konzept W1 §1).
 *
 * Alle Felder sind optional; was fehlt, bleibt wie es ist. Der Bulk-Service der Oberflaeche
 * braucht trotzdem den vollstaendigen Satz — den baut der Lese-/Schreibdienst aus dem Termin.
 *
 * `all_day` (core-api-lookups) hat drei Zustaende: `true` macht den Termin ganztaegig, `false`
 * nimmt ihm diese Eigenschaft, und ohne das Feld bleibt sie, wie sie ist. Es faellt deshalb nicht
 * unter das `array_filter` in `changes()`.
 *
 * `end_day` (ai-shift-planning, Erweiterung 3) macht den Termin mehrtaegig; dass es nicht vor `day`
 * bzw. dem Starttag liegt, prueft `AiEventWriteService` (422).
 */
class UpdateAiEventRequest extends AiWriteRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'name' => ['nullable', 'string'],
            'day' => ['nullable', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'room_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'all_day' => ['nullable', 'boolean'],
            // Mehrtaegige Termine (ai-shift-planning, Erweiterung 3): das Ende liegt an `end_day` um
            // `end_time`. `>= day` bzw. Starttag prueft `AiEventWriteService` (422).
            'end_day' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $changes = array_filter(
            [
                'name' => $this->validated('name'),
                'day' => $this->validated('day'),
                'start_time' => $this->validated('start_time'),
                'end_time' => $this->validated('end_time'),
                'end_day' => $this->validated('end_day'),
                'room_id' => $this->validated('room_id'),
                'type_id' => $this->validated('type_id'),
            ],
            static fn (mixed $value): bool => $value !== null
        );

        // `false` ist hier eine Aussage und kein fehlendes Feld — deshalb neben dem Filter.
            if ($this->validated('all_day') !== null) {
                $changes['all_day'] = $this->boolean('all_day');
            }

            return $changes;
    }
}

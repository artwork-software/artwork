<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * PATCH /api/v1/ai/events/{event} (core-write-endpoints-dry-run; Konzept W1 §1).
 *
 * Alle Felder sind optional; was fehlt, bleibt wie es ist. Der Bulk-Service der Oberflaeche
 * braucht trotzdem den vollstaendigen Satz — den baut der Lese-/Schreibdienst aus dem Termin.
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        return array_filter(
            [
                'name' => $this->validated('name'),
                'day' => $this->validated('day'),
                'start_time' => $this->validated('start_time'),
                'end_time' => $this->validated('end_time'),
                'room_id' => $this->validated('room_id'),
                'type_id' => $this->validated('type_id'),
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}

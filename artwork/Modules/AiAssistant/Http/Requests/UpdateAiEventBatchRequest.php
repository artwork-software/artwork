<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;

/**
 * PATCH /api/v1/ai/events/batch (ai-scenarios-4 S12; Werkzeug `update_events`).
 *
 * Mindestens eins von `shift_minutes` (±, hoechstens eine Woche), `move_to_day` (Y-m-d, Uhrzeit
 * bleibt) und `room_id`; Kombinationen sind erlaubt (erst `move_to_day`, dann `shift_minutes`).
 */
class UpdateAiEventBatchRequest extends AiWriteRequest
{
    public const MAX_EVENTS = 50;

    public const MAX_SHIFT_MINUTES = 10080;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'event_ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_EVENTS],
            'event_ids.*' => ['integer', 'distinct'],
            'shift_minutes' => [
                'nullable',
                'integer',
                'min:-' . self::MAX_SHIFT_MINUTES,
                'max:' . self::MAX_SHIFT_MINUTES,
                'not_in:0',
            ],
            'move_to_day' => ['nullable', 'date_format:Y-m-d'],
            'room_id' => ['nullable', 'integer'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if (
                $this->input('shift_minutes') === null
                && $this->input('move_to_day') === null
                && $this->input('room_id') === null
            ) {
                $validator->errors()->add(
                    'shift_minutes',
                    'Mindestens shift_minutes, move_to_day oder room_id angeben.'
                );
            }
        });
    }

    /**
     * @return list<int>
     */
    public function eventIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('event_ids')));
    }

    public function intOrNull(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }
}

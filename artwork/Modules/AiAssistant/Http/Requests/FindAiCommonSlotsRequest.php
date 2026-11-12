<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/people/common-free-slots (ai-scenarios-4 S14). `from`/`to` wie die
 * Planungs-Endpunkte (`YYYY-MM-DD` oder RFC 3339, hoechstens 92 Tage), 2–10 Personen.
 */
class FindAiCommonSlotsRequest extends FormRequest
{
    use AiPlanningRangeRules;

    private const TIME = '/^(([01]\d|2[0-3]):[0-5]\d|24:00)$/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->planningRangeRules(), [
            'people' => ['required', 'array', 'min:2', 'max:10'],
            'people.*.type' => ['required', Rule::in(AiPeopleService::TYPES)],
            'people.*.id' => ['required', 'integer'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'day_start' => ['nullable', 'string', 'regex:' . self::TIME],
            'day_end' => ['nullable', 'string', 'regex:' . self::TIME],
            'room_id' => ['nullable', 'integer'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validatePlanningRange($validator);

            if ($validator->errors()->hasAny(['day_start', 'day_end'])) {
                return;
            }

            if ($this->dayStart() >= $this->dayEnd()) {
                $validator->errors()->add('day_end', 'day_end muss nach day_start liegen.');
            }
        });
    }

    /**
     * @return list<array{type: string, id: int}>
     */
    public function people(): array
    {
        $people = [];

        foreach ((array) $this->validated('people') as $person) {
            $key = $person['type'] . '#' . (int) $person['id'];
            $people[$key] = ['type' => (string) $person['type'], 'id' => (int) $person['id']];
        }

        return array_values($people);
    }

    public function dayStart(): string
    {
        return (string) ($this->input('day_start') ?? '08:00');
    }

    public function dayEnd(): string
    {
        return (string) ($this->input('day_end') ?? '20:00');
    }

    public function durationMinutes(): int
    {
        return (int) $this->validated('duration_minutes');
    }

    public function roomId(): ?int
    {
        return $this->validated('room_id') === null ? null : (int) $this->validated('room_id');
    }
}

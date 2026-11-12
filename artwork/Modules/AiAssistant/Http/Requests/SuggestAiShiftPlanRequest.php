<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/shift-plan/suggest (ai-shift-planning, Core 3). Schreibt nichts.
 *
 * `fill`: `max` (Standard) waehlt aus vorhandenen Schichten mit offenem Bedarf, `none` waehlt
 * nichts Vorhandenes aus und bewertet nur `new_shifts`.
 */
class SuggestAiShiftPlanRequest extends FormRequest
{
    use AiPlanningRangeRules;

    public const FILL_MAX = 'max';

    public const FILL_NONE = 'none';

    public const MAX_NEW_SHIFTS = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(self::personRules(), $this->planningRangeRules(), [
            'max_minutes_per_week' => ['nullable', 'integer', 'min:0'],
            'max_minutes_per_month' => ['nullable', 'integer', 'min:0'],
            'max_minutes_total' => ['nullable', 'integer', 'min:0'],
            'fill' => ['nullable', Rule::in([self::FILL_MAX, self::FILL_NONE])],
            'exclude_weeks' => ['nullable', 'array'],
            'exclude_weeks.*' => ['string', 'regex:/^\d{4}-W\d{2}$/'],
            'exclude_days' => ['nullable', 'array'],
            'exclude_days.*' => ['date_format:Y-m-d'],
            'craft_id' => ['nullable', 'integer'],
        ], self::newShiftRules());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function personRules(): array
    {
        return [
            'person' => ['required', 'array'],
            'person.type' => ['required', Rule::in(AiPeopleService::TYPES)],
            'person.id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function newShiftRules(): array
    {
        return [
            'new_shifts' => ['nullable', 'array', 'max:' . self::MAX_NEW_SHIFTS],
            'new_shifts.*.event_id' => ['required', 'integer', 'exists:events,id'],
            'new_shifts.*.craft_id' => ['required', 'integer', 'exists:crafts,id'],
            'new_shifts.*.shift_qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
            'new_shifts.*.start' => ['required', 'date_format:Y-m-d\TH:i'],
            'new_shifts.*.end' => ['required', 'date_format:Y-m-d\TH:i', 'after:new_shifts.*.start'],
            'new_shifts.*.break_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validatePlanningRange($validator));
    }

    /**
     * @return array{
     *     max_minutes_per_week: int|null, max_minutes_per_month: int|null, max_minutes_total: int|null,
     *     fill: string, exclude_weeks: list<string>, exclude_days: list<string>, craft_id: int|null
     * }
     */
    public function options(): array
    {
        $int = fn (string $key): ?int => $this->validated($key) === null ? null : (int) $this->validated($key);

        return [
            'max_minutes_per_week' => $int('max_minutes_per_week'),
            'max_minutes_per_month' => $int('max_minutes_per_month'),
            'max_minutes_total' => $int('max_minutes_total'),
            'fill' => (string) ($this->validated('fill') ?? self::FILL_MAX),
            'exclude_weeks' => array_values(array_map('strval', (array) $this->validated('exclude_weeks', []))),
            'exclude_days' => array_values(array_map('strval', (array) $this->validated('exclude_days', []))),
            'craft_id' => $int('craft_id'),
        ];
    }

    /**
     * @return list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }>
     */
    public function newShifts(): array
    {
        return self::normalizeNewShifts((array) $this->validated('new_shifts', []));
    }

    /**
     * @param array<int, mixed> $rows
     * @return list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }>
     */
    public static function normalizeNewShifts(array $rows): array
    {
        return array_values(array_map(static fn (array $row): array => [
            'event_id' => (int) $row['event_id'],
            'craft_id' => (int) $row['craft_id'],
            'shift_qualification_id' => (int) $row['shift_qualification_id'],
            'start' => (string) $row['start'],
            'end' => (string) $row['end'],
            'break_minutes' => isset($row['break_minutes']) ? (int) $row['break_minutes'] : null,
        ], $rows));
    }
}

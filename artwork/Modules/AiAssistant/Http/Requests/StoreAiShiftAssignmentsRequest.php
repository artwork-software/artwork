<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * POST /api/v1/ai/shift-assignments (ai-shift-planning, Core 4; Werkzeug `assign_shifts`).
 *
 * `assignments`: vorhandene Schichten mit Funktion. `new_shifts`: Schichten, die zuerst angelegt
 * und dann mit der Person besetzt werden (Form wie bei `suggest`). Mindestens eines von beiden.
 */
class StoreAiShiftAssignmentsRequest extends AiWriteRequest
{
    public const MAX_ASSIGNMENTS = 50;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->writeRules(),
            SuggestAiShiftPlanRequest::personRules(),
            [
                'assignments' => ['nullable', 'array', 'max:' . self::MAX_ASSIGNMENTS],
                'assignments.*.shift_id' => ['required', 'integer', 'distinct'],
                'assignments.*.shift_qualification_id' => ['required', 'integer'],
            ],
            SuggestAiShiftPlanRequest::newShiftRules(),
        );
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (\Illuminate\Contracts\Validation\Validator $validator): void {
            if ((array) $this->input('assignments', []) === [] && (array) $this->input('new_shifts', []) === []) {
                $validator->errors()->add('assignments', 'Mindestens eine Zuweisung oder neue Schicht ist noetig.');
            }
        });
    }

    /**
     * @return list<array{shift_id: int, shift_qualification_id: int}>
     */
    public function assignments(): array
    {
        return array_values(array_map(static fn (array $row): array => [
            'shift_id' => (int) $row['shift_id'],
            'shift_qualification_id' => (int) $row['shift_qualification_id'],
        ], (array) $this->validated('assignments', [])));
    }

    /**
     * @return list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }>
     */
    public function newShifts(): array
    {
        return SuggestAiShiftPlanRequest::normalizeNewShifts((array) $this->validated('new_shifts', []));
    }
}

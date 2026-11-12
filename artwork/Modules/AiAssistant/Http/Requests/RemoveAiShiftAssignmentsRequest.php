<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * POST /api/v1/ai/shift-assignments/remove (Erweiterung 2 E; Werkzeug `unassign_shifts`).
 */
class RemoveAiShiftAssignmentsRequest extends AiWriteRequest
{
    public const MAX_SHIFTS = 50;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->writeRules(),
            SuggestAiShiftPlanRequest::personRules(),
            [
                'shift_ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_SHIFTS],
                'shift_ids.*' => ['integer', 'distinct'],
            ],
        );
    }

    /**
     * @return list<int>
     */
    public function shiftIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('shift_ids', [])));
    }
}

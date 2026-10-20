<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/shift-assignments/bulk (ai-scenarios-3 S3; Werkzeug `assign_shifts_bulk`).
 */
class StoreAiShiftAssignmentsBulkRequest extends AiWriteRequest
{
    public const MAX_ROWS = 100;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'assignments' => ['required', 'array', 'min:1', 'max:' . self::MAX_ROWS],
            'assignments.*.person' => ['required', 'array'],
            'assignments.*.person.type' => ['required', Rule::in(AiPeopleService::TYPES)],
            'assignments.*.person.id' => ['required', 'integer'],
            'assignments.*.shift_id' => ['required', 'integer'],
            'assignments.*.shift_qualification_id' => ['required', 'integer'],
        ]);
    }

    /**
     * @return list<array{person: array{type: string, id: int}, shift_id: int, shift_qualification_id: int}>
     */
    public function rows(): array
    {
        return array_values(array_map(static fn (array $row): array => [
            'person' => ['type' => (string) $row['person']['type'], 'id' => (int) $row['person']['id']],
            'shift_id' => (int) $row['shift_id'],
            'shift_qualification_id' => (int) $row['shift_qualification_id'],
        ], (array) $this->validated('assignments')));
    }
}

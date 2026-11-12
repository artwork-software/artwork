<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiAbsenceWriteService;
use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/shift-assignments/replace (ai-scenarios-4 S9; Werkzeug `replace_in_shifts`).
 */
class ReplaceAiShiftAssignmentsRequest extends AiWriteRequest
{
    public const MAX_ROWS = 50;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'replacements' => ['required', 'array', 'min:1', 'max:' . self::MAX_ROWS],
            'replacements.*.shift_id' => ['required', 'integer'],
            'replacements.*.shift_qualification_id' => ['required', 'integer'],
            'replacements.*.from' => ['required', 'array'],
            'replacements.*.from.type' => ['required', Rule::in(AiPeopleService::TYPES)],
            'replacements.*.from.id' => ['required', 'integer'],
            'replacements.*.to' => ['required', 'array'],
            'replacements.*.to.type' => ['required', Rule::in(AiPeopleService::TYPES)],
            'replacements.*.to.id' => ['required', 'integer'],
            'mark_absent' => ['nullable', 'array'],
            'mark_absent.type' => ['required_with:mark_absent', Rule::in(AiAbsenceWriteService::TYPES)],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('replacements', []) as $index => $row) {
                if (
                    is_array($row)
                    && ($row['from']['type'] ?? null) === ($row['to']['type'] ?? null)
                    && (int) ($row['from']['id'] ?? 0) === (int) ($row['to']['id'] ?? -1)
                ) {
                    $validator->errors()->add(
                        "replacements.{$index}.to",
                        'Die Ersatzperson muss eine andere Person sein.'
                    );
                }
            }
        });
    }

    /**
     * @return list<array{
     *     shift_id: int, shift_qualification_id: int,
     *     from: array{type: string, id: int}, to: array{type: string, id: int}
     * }>
     */
    public function rows(): array
    {
        return array_values(array_map(static fn (array $row): array => [
            'shift_id' => (int) $row['shift_id'],
            'shift_qualification_id' => (int) $row['shift_qualification_id'],
            'from' => ['type' => (string) $row['from']['type'], 'id' => (int) $row['from']['id']],
            'to' => ['type' => (string) $row['to']['type'], 'id' => (int) $row['to']['id']],
        ], (array) $this->validated('replacements')));
    }

    public function markAbsent(): ?string
    {
        $type = $this->validated('mark_absent.type');

        return is_string($type) ? $type : null;
    }
}

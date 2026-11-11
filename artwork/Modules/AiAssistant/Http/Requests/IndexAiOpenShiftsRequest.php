<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/shifts/open?from=&to=&craft_id=&limit= (ai-shift-planning, Erweiterung 2 A).
 */
class IndexAiOpenShiftsRequest extends FormRequest
{
    use AiPlanningRangeRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->planningRangeRules(), [
            'craft_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validatePlanningRange($validator));
    }

    public function craftId(): ?int
    {
        return $this->validated('craft_id') === null ? null : (int) $this->validated('craft_id');
    }

    public function limit(): ?int
    {
        return $this->validated('limit') === null ? null : (int) $this->validated('limit');
    }
}

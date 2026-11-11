<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/ai/shift-plan/replacements (ai-scenarios-4 S9). Schreibt nichts.
 *
 * `from`/`to` als `YYYY-MM-DD` oder RFC 3339 (`AiPlanningRangeRules`), hoechstens 92 Tage.
 */
class SuggestAiReplacementsRequest extends FormRequest
{
    use AiPlanningRangeRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(SuggestAiShiftPlanRequest::personRules(), $this->planningRangeRules(), [
            'craft_id' => ['nullable', 'integer'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validatePlanningRange($validator));
    }

    public function craftId(): ?int
    {
        $craftId = $this->validated('craft_id');

        return $craftId === null ? null : (int) $craftId;
    }
}

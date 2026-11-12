<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/people/{type}/{id}/workload?from=&to= (ai-shift-planning), hoechstens 92 Tage.
 */
class ShowAiPersonWorkloadRequest extends FormRequest
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
        return $this->planningRangeRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validatePlanningRange($validator));
    }
}

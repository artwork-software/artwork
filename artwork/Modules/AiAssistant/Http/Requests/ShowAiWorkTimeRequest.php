<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/people/{type}/{id}/work-time?month=YYYY-MM (ai-scenarios-3 S7); ohne `month` der
 * laufende Monat.
 */
class ShowAiWorkTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['month' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']];
    }

    public function monthStart(): CarbonImmutable
    {
        $month = $this->validated('month');

        return $month === null
            ? CarbonImmutable::today()->startOfMonth()
            : CarbonImmutable::parse((string) $month . '-01')->startOfDay();
    }
}

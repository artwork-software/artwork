<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/day-overview?date=YYYY-MM-DD (ai-scenarios-3 S1); ohne `date` heute.
 */
class ShowAiDayOverviewRequest extends FormRequest
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
        return ['date' => ['nullable', 'date_format:Y-m-d']];
    }

    public function day(): CarbonImmutable
    {
        $date = $this->validated('date');

        return ($date === null ? CarbonImmutable::today() : CarbonImmutable::parse((string) $date))->startOfDay();
    }
}

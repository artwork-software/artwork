<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/me/schedule (core-api-schedule-shifts; Konzept B1 §3, §6).
 *
 * Bewusst **ohne** `user_id`: fremde Einsatzplaene gibt es nur ueber `/ai/shift-plan` mit dessen
 * Rechtepruefung. Ein Parameter, der „normalerweise" die eigene Person meint, wird vom Modell
 * irgendwann anders gesetzt (B1 §6).
 */
class ShowAiScheduleRequest extends AiTimeRangeRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}

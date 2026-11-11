<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/absences?from=&to=&craft_id=&q=&limit= (ai-scenarios-3 S4), hoechstens 92 Tage.
 */
class IndexAiAbsencesRequest extends IndexAiOpenShiftsRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), ['q' => ['nullable', 'string']]);
    }

    public function search(): ?string
    {
        $search = $this->validated('q');

        return is_string($search) && $search !== '' ? $search : null;
    }
}

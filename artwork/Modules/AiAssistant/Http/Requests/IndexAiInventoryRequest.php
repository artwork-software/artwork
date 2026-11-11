<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/inventory/availability (core-api-inventory; Konzept B1 §3).
 */
class IndexAiInventoryRequest extends AiTimeRangeRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'q' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}

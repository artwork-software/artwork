<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/people/{type}/{id}/shifts?from=&to= (core-ai-aggregates, Entgrenzen P1).
 */
class ShowAiPersonShiftsRequest extends AiTimeRangeRequest
{
    public const DEFAULT_LIMIT = 500;

    public const MAX_LIMIT = 2000;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->rangeRules(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
        ]);
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiWorkTimeService;
use Illuminate\Validation\Rule;

/**
 * GET /api/v1/ai/work-time/overview?month=YYYY-MM&craft_id=&sort=balance|difference&limit=
 * (ai-scenarios-4 S16); ohne `month` der laufende Monat, ohne `sort` nach Kontostand.
 */
class ShowAiWorkTimeOverviewRequest extends ShowAiWorkTimeRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'craft_id' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in([AiWorkTimeService::SORT_BALANCE, AiWorkTimeService::SORT_DIFFERENCE])],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function craftId(): ?int
    {
        return $this->validated('craft_id') === null ? null : (int) $this->validated('craft_id');
    }

    public function sort(): string
    {
        return (string) ($this->validated('sort') ?? AiWorkTimeService::SORT_BALANCE);
    }

    public function limit(): ?int
    {
        return $this->validated('limit') === null ? null : (int) $this->validated('limit');
    }
}

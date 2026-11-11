<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * DELETE /api/v1/ai/events/batch (core-write-endpoints-dry-run; Konzept W1 §1).
 */
class DeleteAiEventBatchRequest extends AiWriteRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'ids' => ['required', 'array', 'min:1', 'max:' . (int) config('ai.list_limit')],
            'ids.*' => ['integer'],
        ]);
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var list<int|string> $ids */
        $ids = (array) $this->validated('ids');

        return array_values(array_unique(array_map('intval', $ids)));
    }
}

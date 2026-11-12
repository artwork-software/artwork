<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/contracts?due_before=&project_id=&q=&limit= (ai-scenarios-4 S15).
 */
class IndexAiContractsRequest extends AiLookupRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'due_before' => ['nullable', 'date_format:Y-m-d'],
            'project_id' => ['nullable', 'integer'],
        ]);
    }

    public function dueBefore(): ?string
    {
        $value = $this->validated('due_before');

        return is_string($value) ? $value : null;
    }

    public function projectId(): ?int
    {
        return $this->validated('project_id') === null ? null : (int) $this->validated('project_id');
    }
}

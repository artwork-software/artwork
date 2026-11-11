<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/checklists?project_id=&q=&limit= (ai-scenarios-4 S10, Lookup).
 */
class IndexAiChecklistsRequest extends AiLookupRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), ['project_id' => ['nullable', 'integer']]);
    }

    public function projectId(): ?int
    {
        return $this->validated('project_id') === null ? null : (int) $this->validated('project_id');
    }
}

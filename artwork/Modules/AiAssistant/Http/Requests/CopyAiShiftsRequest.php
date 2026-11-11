<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * POST /api/v1/ai/shifts/copy (ai-scenarios-4 S13; Werkzeug `copy_shifts`).
 */
class CopyAiShiftsRequest extends AiWriteRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'source_event_id' => ['required', 'integer'],
            'target_event_id' => ['required', 'integer', 'different:source_event_id'],
            'craft_id' => ['nullable', 'integer'],
            'with_assignments' => ['sometimes', 'boolean'],
        ]);
    }

    public function intOrNull(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }
}

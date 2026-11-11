<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * PATCH /api/v1/ai/tasks/{task} (ai-scenarios-3 S5; Werkzeug `update_task`). `task_id` steht wie
 * `event_id` bei `update_event` im Hash; im Rumpf ist es optional, muss aber zum Pfad passen.
 */
class UpdateAiTaskRequest extends AiWriteRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'task_id' => ['nullable', 'integer'],
            'done' => ['required', 'boolean'],
        ]);
    }
}

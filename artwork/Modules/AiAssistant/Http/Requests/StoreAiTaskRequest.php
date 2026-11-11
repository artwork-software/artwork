<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;

/**
 * POST /api/v1/ai/tasks (ai-scenarios-4 S10; Werkzeug `create_task`).
 *
 * `checklist_id` und `project_id` schliessen sich aus; ohne beide landet die Aufgabe in der
 * privaten Checkliste der Person. `deadline` als `Y-m-d` oder RFC 3339 (es zaehlt der Kalendertag
 * in Haus-Zeitzone, die Oberflaeche setzt die Frist auf Tagesende).
 */
class StoreAiTaskRequest extends AiWriteRequest
{
    public const MAX_ASSIGNEES = 20;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'deadline' => ['nullable', 'date'],
            'checklist_id' => ['nullable', 'integer', 'prohibits:project_id'],
            'project_id' => ['nullable', 'integer'],
            'assignee_ids' => ['nullable', 'array', 'max:' . self::MAX_ASSIGNEES],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
    }

    public function deadlineDay(): ?string
    {
        $deadline = $this->validated('deadline');

        if (!is_string($deadline) || $deadline === '') {
            return null;
        }

        $timezone = (string) config('app.timezone');

        return CarbonImmutable::parse($deadline, $timezone)->setTimezone($timezone)->toDateString();
    }

    public function intOrNull(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }

    /**
     * @return list<int>
     */
    public function assigneeIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('assignee_ids', [])));
    }
}

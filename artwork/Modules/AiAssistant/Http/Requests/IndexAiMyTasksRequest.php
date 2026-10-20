<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiTaskService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /api/v1/ai/me/tasks?status=open|overdue|done&project_id=&q=&limit= (ai-scenarios-3 S5).
 */
class IndexAiMyTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in([
                AiTaskService::STATUS_OPEN,
                AiTaskService::STATUS_OVERDUE,
                AiTaskService::STATUS_DONE,
            ])],
            'project_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function status(): string
    {
        return (string) ($this->validated('status') ?? AiTaskService::STATUS_OPEN);
    }

    public function intOrNull(string $key): ?int
    {
        return $this->validated($key) === null ? null : (int) $this->validated($key);
    }

    public function search(): ?string
    {
        $search = $this->validated('q');

        return is_string($search) && $search !== '' ? $search : null;
    }
}

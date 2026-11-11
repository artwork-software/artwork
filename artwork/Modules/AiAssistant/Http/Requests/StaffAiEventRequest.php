<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/shift-plan/staff-event (ai-scenarios-3 S3). Schreibt nichts.
 */
class StaffAiEventRequest extends FormRequest
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
            'event_id' => ['required', 'integer'],
            'craft_id' => ['required', 'integer'],
            'qualification_id' => ['nullable', 'integer'],
            'people' => ['nullable', 'array', 'max:50'],
            'people.*.type' => ['required', Rule::in(AiPeopleService::TYPES)],
            'people.*.id' => ['required', 'integer'],
            'max_minutes_per_person_week' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return list<array{type: string, id: int}>|null
     */
    public function people(): ?array
    {
        $people = $this->validated('people');

        return $people === null ? null : array_values(array_map(static fn (array $person): array => [
            'type' => (string) $person['type'],
            'id' => (int) $person['id'],
        ], (array) $people));
    }

    public function intOrNull(string $key): ?int
    {
        return $this->validated($key) === null ? null : (int) $this->validated($key);
    }
}

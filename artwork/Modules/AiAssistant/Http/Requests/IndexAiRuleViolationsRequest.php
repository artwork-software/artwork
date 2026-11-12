<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * GET /api/v1/ai/shift-rule-violations?from=&to=&craft_id=&status=&limit= (Erweiterung 2 B).
 * `status`: `active` (Standard), `resolved`, `ignored` oder `all`.
 */
class IndexAiRuleViolationsRequest extends IndexAiOpenShiftsRequest
{
    public const STATUSES = ['active', 'resolved', 'ignored', 'all'];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);
    }

    public function status(): string
    {
        return (string) ($this->validated('status') ?? 'active');
    }
}

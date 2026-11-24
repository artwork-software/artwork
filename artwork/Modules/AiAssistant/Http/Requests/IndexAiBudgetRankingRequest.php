<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /api/v1/ai/projects/budget-ranking (core-ai-aggregates, Entgrenzen P1).
 *
 * `period` ist der Abrechnungszeitraum, also der Name (oder Untertitel) einer Budgetspalte, z. B. „2026".
 */
class IndexAiBudgetRankingRequest extends FormRequest
{
    public const SORTS = ['earnings', 'costs', 'result'];

    public const DEFAULT_LIMIT = 50;

    public const MAX_LIMIT = 500;

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
            'period' => ['nullable', 'string', 'min:1', 'max:20'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
            'state_id' => ['nullable', 'integer'],
            'include_groups' => ['nullable', 'boolean'],
        ];
    }

    public function period(): ?string
    {
        $period = trim((string) $this->validated('period'));

        return $period === '' ? null : $period;
    }

    public function sort(): string
    {
        return (string) ($this->validated('sort') ?? 'earnings');
    }

    public function direction(): string
    {
        return (string) ($this->validated('direction') ?? 'desc');
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? self::DEFAULT_LIMIT);
    }

    public function stateId(): ?int
    {
        $value = $this->validated('state_id');

        return $value === null ? null : (int) $value;
    }

    public function includeGroups(): bool
    {
        return $this->boolean('include_groups');
    }
}

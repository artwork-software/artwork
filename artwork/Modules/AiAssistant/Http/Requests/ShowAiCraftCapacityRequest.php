<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/crafts/{craft}/capacity?week=YYYY-Www (Erweiterung 2 D).
 */
class ShowAiCraftCapacityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'week' => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4]\d|5[0-3])$/'],
        ];
    }

    public function weekStart(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $this->validated('week') . '-1')->startOfDay();
    }
}

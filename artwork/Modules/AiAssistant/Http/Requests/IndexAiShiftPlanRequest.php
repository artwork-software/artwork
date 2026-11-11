<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/shift-plan (core-api-schedule-shifts; Konzept B1 §3).
 *
 * Der Zeitraum steckt in `week` (ISO-Woche, z.B. `2026-W38`) und ist Pflicht — der Ausschnitt ist
 * damit nie groesser als eine Woche, die 180-Tage-Grenze der uebrigen Endpunkte kann hier nicht
 * gerissen werden.
 */
class IndexAiShiftPlanRequest extends FormRequest
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
            'craft_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function weekStart(): CarbonImmutable
    {
        [$year, $week] = explode('-W', (string) $this->validated('week'));

        return CarbonImmutable::now()
            ->setISODate((int) $year, (int) $week)
            ->startOfDay();
    }

    public function weekEnd(): CarbonImmutable
    {
        return $this->weekStart()->addDays(6)->endOfDay();
    }

    public function craftId(): ?int
    {
        $craftId = $this->validated('craft_id');

        return $craftId === null ? null : (int) $craftId;
    }
}

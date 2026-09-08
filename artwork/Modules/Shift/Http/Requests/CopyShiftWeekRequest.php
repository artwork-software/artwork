<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Core\Services\HelperService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * „Woche kopieren": Quell-KW + 1–8 Ziel-KWs (≠ Quelle), optional auf Gewerke/Räume eingegrenzt.
 * Die Rechteprüfung (can plan shifts) hängt an der Route; das Gewerks-Scoping (nur planbare
 * Gewerke der Person) macht der Controller über CraftScopeService.
 */
class CopyShiftWeekRequest extends FormRequest
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
            'source_week' => ['required', 'integer', 'min:1', 'max:53'],
            'source_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'targets' => ['required', 'array', 'min:1', 'max:8'],
            'targets.*.week' => ['required', 'integer', 'min:1', 'max:53'],
            'targets.*.year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'craft_ids' => ['nullable', 'array', 'max:100'],
            'craft_ids.*' => ['integer', 'exists:crafts,id'],
            'room_ids' => ['nullable', 'array', 'max:100'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sourceWeek = (int) $this->input('source_week');
            $sourceYear = (int) $this->input('source_year');
            $seen = [];

            // KW 53 existiert nur in 53-Wochen-Jahren (2026 ja, 2025 nein) — Carbon würde sonst still
            // in KW 1 des Folgejahres überrollen und die falsche Woche kopieren.
            if ($sourceWeek >= 1 && $sourceYear >= 1 && !HelperService::isoWeekExists($sourceWeek, $sourceYear)) {
                $validator->errors()->add(
                    'source_week',
                    __('Calendar week :week does not exist in :year.', ['week' => $sourceWeek, 'year' => $sourceYear])
                );
            }

            foreach ((array) $this->input('targets', []) as $index => $target) {
                $week = (int) ($target['week'] ?? 0);
                $year = (int) ($target['year'] ?? 0);

                if ($week >= 1 && $year >= 1 && !HelperService::isoWeekExists($week, $year)) {
                    $validator->errors()->add(
                        "targets.{$index}",
                        __('Calendar week :week does not exist in :year.', ['week' => $week, 'year' => $year])
                    );
                }

                if ($week === $sourceWeek && $year === $sourceYear) {
                    $validator->errors()->add(
                        "targets.{$index}",
                        __('The target week must differ from the source week.')
                    );
                }

                $key = $year . '-' . $week;
                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "targets.{$index}",
                        __('Each target week may only be selected once.')
                    );
                }
                $seen[$key] = true;
            }
        });
    }

    /**
     * @return array<int, int>|null
     */
    public function craftIds(): ?array
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->input('craft_ids', []))));

        return $ids === [] ? null : $ids;
    }

    /**
     * @return array<int, int>|null
     */
    public function roomIds(): ?array
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->input('room_ids', []))));

        return $ids === [] ? null : $ids;
    }

    /**
     * @return array<int, array{week:int, year:int}>
     */
    public function targets(): array
    {
        return array_map(
            static fn (array $target): array => [
                'week' => (int) $target['week'],
                'year' => (int) $target['year'],
            ],
            array_values((array) $this->input('targets', []))
        );
    }
}

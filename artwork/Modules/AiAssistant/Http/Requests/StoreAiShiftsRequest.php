<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;

/**
 * POST /api/v1/ai/shifts (ai-scenarios-4 S13; Werkzeug `create_shifts`).
 *
 * Je Schicht Gewerk, Beginn/Ende (H:i), optional Start-/Endtag (Standard: Tag des Terminbeginns;
 * Ende vor/gleich Beginn = Folgetag), Pause (ohne Angabe die gesetzliche Mindestpause), Beschreibung
 * und die Plaetze je Funktion.
 */
class StoreAiShiftsRequest extends AiWriteRequest
{
    public const MAX_SHIFTS = 30;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), [
            'event_id' => ['required', 'integer'],
            'shifts' => ['required', 'array', 'min:1', 'max:' . self::MAX_SHIFTS],
            'shifts.*.craft_id' => ['required', 'integer', 'exists:crafts,id'],
            'shifts.*.start' => ['required', 'date_format:H:i'],
            'shifts.*.end' => ['required', 'date_format:H:i'],
            'shifts.*.start_day' => ['nullable', 'date_format:Y-m-d'],
            'shifts.*.end_day' => ['nullable', 'date_format:Y-m-d'],
            'shifts.*.break_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'shifts.*.description' => ['nullable', 'string', 'max:10000'],
            'shifts.*.qualifications' => ['required', 'array', 'min:1'],
            'shifts.*.qualifications.*.qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
            'shifts.*.qualifications.*.value' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        // `distinct` auf `shifts.*.qualifications.*` vergliche ueber alle Schichten hinweg.
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('shifts', []) as $index => $row) {
                $ids = array_column(is_array($row) ? (array) ($row['qualifications'] ?? []) : [], 'qualification_id');
                if (count($ids) !== count(array_unique($ids))) {
                    $validator->errors()->add("shifts.{$index}.qualifications", 'Jede Funktion nur einmal je Schicht.');
                }
            }
        });
    }

    /**
     * @return list<array{
     *     craft_id: int, start: string, end: string, start_day: string|null, end_day: string|null,
     *     break_minutes: int|null, description: string|null,
     *     qualifications: list<array{qualification_id: int, value: int}>
     * }>
     */
    public function shifts(): array
    {
        return array_values(array_map(static fn (array $row): array => [
            'craft_id' => (int) $row['craft_id'],
            'start' => (string) $row['start'],
            'end' => (string) $row['end'],
            'start_day' => isset($row['start_day']) ? (string) $row['start_day'] : null,
            'end_day' => isset($row['end_day']) ? (string) $row['end_day'] : null,
            'break_minutes' => isset($row['break_minutes']) ? (int) $row['break_minutes'] : null,
            'description' => isset($row['description']) ? (string) $row['description'] : null,
            'qualifications' => array_values(array_map(static fn (array $qualification): array => [
                'qualification_id' => (int) $qualification['qualification_id'],
                'value' => (int) $qualification['value'],
            ], (array) $row['qualifications'])),
        ], (array) $this->validated('shifts')));
    }
}

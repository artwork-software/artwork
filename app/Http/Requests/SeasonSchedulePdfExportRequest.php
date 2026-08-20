<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SeasonSchedulePdfExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:startDate',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $start = Carbon::createFromFormat('Y-m-d', (string) $this->input('startDate'));
                    $end = Carbon::createFromFormat('Y-m-d', (string) $value);

                    // 24 Monate = 4 Seiten à 6 Monate; mehr wird kein sinnvoller Wandkalender mehr
                    if (
                        $start !== false
                        && $end !== false
                        && $start->startOfMonth()->diffInMonths($end->startOfMonth()) >= 24
                    ) {
                        $fail('The export period must not exceed 24 months.');
                    }
                },
            ],
            'paperSize' => ['required', Rule::in(['a3', 'a4'])],
            'dpi' => ['required', 'integer', 'between:72,300'],
            // Sub-Arrays einzeln validieren: validated() strippt unvalidierte nested Keys
            'filter' => ['sometimes', 'nullable', 'array'],
            'filter.event_type_ids' => ['sometimes', 'nullable', 'array'],
            'filter.event_type_ids.*' => ['integer'],
            'filter.room_ids' => ['sometimes', 'nullable', 'array'],
            'filter.room_ids.*' => ['integer'],
            'filter.area_ids' => ['sometimes', 'nullable', 'array'],
            'filter.area_ids.*' => ['integer'],
            'filter.room_attribute_ids' => ['sometimes', 'nullable', 'array'],
            'filter.room_attribute_ids.*' => ['integer'],
            'filter.room_category_ids' => ['sometimes', 'nullable', 'array'],
            'filter.room_category_ids.*' => ['integer'],
            'filter.event_property_ids' => ['sometimes', 'nullable', 'array'],
            'filter.event_property_ids.*' => ['integer'],
            'showHolidays' => ['required', 'boolean'],
            'showWeekNumbers' => ['required', 'boolean'],
            'highlightWeekends' => ['required', 'boolean'],
            'showColorDots' => ['required', 'boolean'],
            'showEventsWithoutProject' => ['required', 'boolean'],
            'showRoomAbbreviations' => ['required', 'boolean'],
            'splitMonths' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'startDate' => $this->normalizeDateInput($this->input('startDate'))
                ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
            'endDate' => $this->normalizeDateInput($this->input('endDate'))
                ?? Carbon::now()->addMonths(5)->endOfMonth()->format('Y-m-d'),
            'paperSize' => $this->input('paperSize', 'a3'),
            'dpi' => $this->integer('dpi') ?: 72,
            'showHolidays' => $this->has('showHolidays') ? $this->boolean('showHolidays') : true,
            'showWeekNumbers' => $this->has('showWeekNumbers') ? $this->boolean('showWeekNumbers') : true,
            'highlightWeekends' => $this->has('highlightWeekends') ? $this->boolean('highlightWeekends') : true,
            'showColorDots' => $this->has('showColorDots') ? $this->boolean('showColorDots') : true,
            'showEventsWithoutProject' => $this->boolean('showEventsWithoutProject'),
            'showRoomAbbreviations' => $this->boolean('showRoomAbbreviations'),
            'splitMonths' => $this->boolean('splitMonths'),
        ]);
    }

    /**
     * Normalisiert Datums-Eingaben auf "YYYY-MM-DD". Akzeptiert "YYYY-MM-DD", "YYYY-MM"
     * (= Monatserster) und "DD.MM.YYYY" (Browser ohne date-Input-Support liefern Freitext).
     */
    private function normalizeDateInput(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-01';
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        return null;
    }
}

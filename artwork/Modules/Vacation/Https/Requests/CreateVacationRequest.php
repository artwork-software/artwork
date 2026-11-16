<?php

namespace Artwork\Modules\Vacation\Https\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property mixed type          'vacation' | 'available' (Abwesenheit oder Verfügbarkeit)
 */
class CreateVacationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Ohne Format- und Serienprüfung endeten ungültige Werte in SQL- bzw. TypeErrors (500)
        return [
            'start_time' => 'nullable|date_format:H:i,H:i:s',
            'end_time' => 'nullable|date_format:H:i,H:i:s',
            'date' => 'required|date',
            'type' => 'required|string',
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:100',
            'is_series' => 'nullable|boolean',
            'series_repeat' => [
                Rule::requiredIf(fn (): bool => $this->boolean('is_series')),
                'nullable',
                'string',
                'in:daily,weekly',
            ],
            'series_repeat_until' => [
                Rule::requiredIf(fn (): bool => $this->boolean('is_series')),
                'nullable',
                'date',
                'after_or_equal:date',
            ],
        ];
    }

    /**
     * full_day und is_series sind NOT-NULL-Spalten: fehlende bzw. leere Flags gelten als false.
     */
    protected function prepareForValidation(): void
    {
        foreach (['full_day', 'is_series'] as $flag) {
            if ($this->input($flag) === null) {
                $this->merge([$flag => false]);
            }
        }
    }
}

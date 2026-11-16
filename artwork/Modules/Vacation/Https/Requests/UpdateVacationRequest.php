<?php

namespace Artwork\Modules\Vacation\Https\Requests;

use Artwork\Modules\Vacation\Rules\MaxSeriesLength;
use Illuminate\Foundation\Http\FormRequest;

/**
* @property mixed id
 * @property mixed $start_time
 * @property mixed $end_time
 * @property mixed $date
 * @property mixed $full_day
 * @property mixed $comment
 * @property mixed $is_series
 * @property mixed $series_repeat
 * @property mixed $series_repeat_until
 * @property mixed $type
 * @property mixed $type_before_update
 */
class UpdateVacationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:vacations,id',
            'start_time' => 'nullable|date_format:H:i,H:i:s',
            'end_time' => 'nullable|date_format:H:i,H:i:s',
            'date' => 'required|date',
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:100',
            'is_series' => 'nullable|boolean',
            'series_repeat' => 'nullable|string',
            'series_repeat_until' => ['nullable', 'date', new MaxSeriesLength()],
            'type' => 'required|string',
            'type_before_update' => 'required|string',
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

<?php

namespace Artwork\Modules\Vacation\Https\Requests;

use Artwork\Modules\Vacation\Enums\Vacation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
* @property mixed id
 * @property mixed start_time
 * @property mixed end_time
 * @property mixed date
 * @property mixed full_day
 * @property mixed comment
 * @property mixed is_series
 * @property mixed series_repeat
 * @property mixed series_repeat_until
 * @property mixed type
 * @property mixed type_before_update
 * @property mixed vacation_type Art der Abwesenheit: OFF_WORK (Urlaub, Default) | NOT_AVAILABLE
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
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'date' => 'required',
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:100',
            'is_series' => 'nullable|boolean',
            'series_repeat' => 'nullable|string',
            'series_repeat_until' => 'nullable|date',
            'type' => 'required|string',
            'type_before_update' => 'required|string',
            'vacation_type' => ['nullable', 'string', Rule::in(Vacation::selfServiceAbsenceValues())],
        ];
    }
}

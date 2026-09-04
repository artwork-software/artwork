<?php

namespace Artwork\Modules\Vacation\Https\Requests;

use Artwork\Modules\Vacation\Enums\Vacation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property mixed type          'vacation' | 'available' (Abwesenheit oder Verfügbarkeit)
 * @property mixed vacation_type Art der Abwesenheit: OFF_WORK (Urlaub, Default) | NOT_AVAILABLE
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
        return [
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'date' => 'required',
            'type' => 'required|string',
            // Art der Abwesenheit (nur bei type=vacation relevant); fehlt sie, gilt Urlaub (OFF_WORK)
            'vacation_type' => ['nullable', 'string', Rule::in(Vacation::selfServiceAbsenceValues())],
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:100',
            'is_series' => 'nullable|boolean',
            'series_repeat' => 'nullable|string',
            'series_repeat_until' => 'nullable|date',
        ];
    }
}

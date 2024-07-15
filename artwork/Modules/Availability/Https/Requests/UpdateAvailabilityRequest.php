<?php

namespace Artwork\Modules\Availability\Https\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
 */
class UpdateAvailabilityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:availabilities,id',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'date' => 'required',
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:20',
            'is_series' => 'nullable|boolean',
            'series_repeat' => 'nullable|string',
            'series_repeat_until' => 'nullable|date',
            'type' => 'required|string',
            'type_before_update' => 'required|string',
        ];
    }
}

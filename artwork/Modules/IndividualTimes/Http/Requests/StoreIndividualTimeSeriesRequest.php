<?php

namespace Artwork\Modules\IndividualTimes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndividualTimeSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // hier ggf. Policy prüfen, erstmal true
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],

            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],

            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time'   => ['nullable', 'date_format:H:i'],
            'full_day'   => ['sometimes', 'boolean'],

            'working_time_minutes' => ['nullable', 'integer', 'min:0'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],

            'frequency' => ['required', 'string', 'in:weekly'], // später erweiterbar
            'interval'  => ['required', 'integer', 'min:1'],

            'weekdays'   => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],

            // Ausgewählte Subjekte (User, Freelancer, ServiceProvider)
            'subjects'          => ['required', 'array', 'min:1'],
            'subjects.*.type'   => ['required', 'string', 'in:user,freelancer,service_provider'],
            'subjects.*.id'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'full_day' => $this->boolean('full_day'),
        ]);
    }
}

<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkTimeBookingRequest extends FormRequest
{
    private const DURATION_PATTERN = '/^\d{1,5}:[0-5]\d$/';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('can manage workers') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            // Dauer als "H:MM" ohne Obergrenze bei den Stunden (Salden > 24 h, z. B. Übernahme bei Produktivstart)
            'hours' => ['required', 'string', 'regex:' . self::DURATION_PATTERN],
            'nightly_working_hours' => ['required', 'string', 'regex:' . self::DURATION_PATTERN],
            'comment' => 'required|string|max:255',
            'plus_minus' => 'required|in:+,-',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hours.regex' => __('Please enter the duration as hours:minutes (e.g. 150:30).'),
            'nightly_working_hours.regex' => __('Please enter the duration as hours:minutes (e.g. 150:30).'),
        ];
    }

    /**
     * @return array<int, \Closure(\Illuminate\Validation\Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['hours', 'nightly_working_hours'])) {
                    return;
                }

                $bookedMinutes = self::durationToMinutes((string) $this->input('hours'));
                $nightMinutes = self::durationToMinutes((string) $this->input('nightly_working_hours'));

                if ($bookedMinutes === 0) {
                    $validator->errors()->add('hours', __('Please enter a duration greater than zero.'));
                }

                if ($nightMinutes > $bookedMinutes) {
                    $validator->errors()->add(
                        'nightly_working_hours',
                        __('The night hours cannot exceed the booked hours.')
                    );
                }
            },
        ];
    }

    public static function durationToMinutes(string $duration): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $duration, 2) + [1 => '0']);

        return $hours * 60 + $minutes;
    }
}

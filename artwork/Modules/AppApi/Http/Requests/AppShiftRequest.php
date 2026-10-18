<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppShiftRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'day' => ['required', 'date_format:Y-m-d'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'description' => ['nullable', 'string', 'max:1000'],
            'craft_id' => ['required', 'integer', 'exists:crafts,id'],
            // Like the web (ShiftController::updateShift): a shift without event
            // needs a room — without one it drops out of the shift plan.
            'room_id' => [
                Rule::requiredIf(fn (): bool => $this->isShiftWithoutEvent()),
                'nullable',
                'integer',
                'exists:rooms,id',
            ],
            // The shift's staffing requirements; values below the people
            // already booked are clamped up to the booked count (core rule).
            'qualifications' => ['present', 'array'],
            'qualifications.*.shift_qualification_id' => [
                'required',
                'integer',
                'exists:shift_qualifications,id',
            ],
            'qualifications.*.value' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * New shifts from the app never belong to an event (store); on update the
     * bound shift decides.
     */
    private function isShiftWithoutEvent(): bool
    {
        $shift = $this->route('shift');

        return !$shift instanceof Shift || $shift->event_id === null;
    }
}

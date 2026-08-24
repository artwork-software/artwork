<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserCalendarAboRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Bewusst ohne user_id und calendar_abo_id: beide werden ausschliesslich serverseitig
     * gesetzt und duerfen nicht ueber den Request beeinflussbar sein.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_range' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'specific_event_types' => 'boolean',
            'event_types' => 'array',
            'event_types.*' => 'integer|exists:event_types,id',
            'specific_rooms' => 'boolean',
            'selected_rooms' => 'array',
            'selected_rooms.*' => 'integer|exists:rooms,id',
            'specific_areas' => 'boolean',
            'selected_areas' => 'array',
            'selected_areas.*' => 'integer|exists:areas,id',
            'enable_notification' => 'boolean',
            'notification_time' => 'nullable|integer|min:0',
            'notification_time_unit' => 'nullable|in:minutes,hours,days',
        ];
    }
}

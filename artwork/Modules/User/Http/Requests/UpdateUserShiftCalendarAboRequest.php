<?php

namespace Artwork\Modules\User\Http\Requests;

use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserShiftCalendarAboRequest extends FormRequest
{
    /**
     * Ein Abo darf nur von seinem Besitzer bearbeitet werden. Ohne diese Pruefung koennte
     * jeder angemeldete Nutzer ueber die ID ein fremdes Abo umkonfigurieren.
     */
    public function authorize(): bool
    {
        $calendarAbo = $this->route('userShiftCalendarAbo');

        return $calendarAbo instanceof UserShiftCalendarAbo
            && $calendarAbo->user_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Bewusst ohne user_id und calendar_abo_id: beide sind serverseitig fixiert.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_range' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'specific_crafts' => 'boolean',
            'craft_ids' => 'array',
            'craft_ids.*' => 'integer|exists:crafts,id',
            'enable_notification' => 'boolean',
            'notification_time' => 'nullable|integer|min:0',
            'notification_time_unit' => 'nullable|in:minutes,hours,days',
        ];
    }
}

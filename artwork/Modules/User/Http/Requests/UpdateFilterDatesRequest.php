<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Zeitraum-Navigation der Kalender-/Dienstplan-/Planungsansichten. Ohne Prüfung wurde ein
 * fehlendes Datum still als „heute“ gespeichert (Carbon::parse(null)), ein vertauschter
 * Zeitraum ergab einen leeren Kalender und unparsbare Werte einen 500.
 */
class UpdateFilterDatesRequest extends FormRequest
{
    /**
     * Rechteprüfung vor der Validierung, damit Fremde weiterhin 403 statt 422 erhalten.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('updateOwnPreferences', $this->route('user'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}

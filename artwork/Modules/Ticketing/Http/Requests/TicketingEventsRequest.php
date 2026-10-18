<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Freigeben oder Zurückziehen: die Termine, die es trifft — einer, mehrere, eine ganze Serie. */
class TicketingEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Bearbeiten des Projekts prüft die Route (CanEditProject).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::eventIdsRule();
    }

    /** @return array<string, string> */
    public static function eventIdsRule(): array
    {
        return [
            'event_ids' => 'required|array|min:1|max:500',
            'event_ids.*' => 'required|integer|distinct',
        ];
    }

    /** @return list<int> */
    public function eventIds(): array
    {
        return array_map('intval', $this->validated('event_ids'));
    }
}

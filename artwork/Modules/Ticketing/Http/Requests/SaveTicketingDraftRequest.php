<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

/** Die Preisklassen aus der Ticketing-Komponente für die angegebenen Termine. */
class SaveTicketingDraftRequest extends TicketingEventsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'classes' => 'required|array|min:1|max:50',
            'classes.*.zone_key' => 'present|nullable|string|max:32',
            'classes.*.name' => 'required|string|max:60',
            'classes.*.price_cents' => 'required|integer|min:0',
            'classes.*.quota' => 'required|integer|min:0|max:1000000',
            ...self::eventIdsRule(),
        ];
    }
}

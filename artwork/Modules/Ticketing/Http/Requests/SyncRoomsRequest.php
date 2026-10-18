<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Erneuter Abgleich der Räume aus dem Tab "Räume & Preisklassen". */
class SyncRoomsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return TicketingDraftRules::rooms();
    }
}

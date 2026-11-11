<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Veranstaltungsplan: optional die Spielstätte (ein Raum), deren Name und Adresse im Kopf stehen.
 */
class ProjectEventPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'venue_room_id' => ['nullable', 'integer', 'exists:rooms,id,deleted_at,NULL'],
        ];
    }
}

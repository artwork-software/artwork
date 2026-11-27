<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Veranstaltungsplan: optional die Spielstätte (ein Raum) und weitere Räume, deren Name und Adresse
 * im Kopf stehen, dazu Logo (Tool-Einstellungen oder eigene Datei) und Einlass der Termine.
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
            'additional_room_ids' => ['nullable', 'array', 'max:30'],
            'additional_room_ids.*' => ['integer', 'exists:rooms,id,deleted_at,NULL'],
            'with_logo' => ['nullable', 'boolean'],
            'with_admission' => ['nullable', 'boolean'],
            // Maßgrenze: Ein kleines WebP mit riesiger Fläche würde beim Umwandeln den Speicher sprengen.
            'logo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:10240',
                'dimensions:max_width=8000,max_height=8000',
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function additionalRoomIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('additional_room_ids', []))));
    }

    /** Ohne Angabe wird das Logo ausgegeben (Standard). */
    public function withLogo(): bool
    {
        return $this->boolean('with_logo', true);
    }

    /** Ohne Angabe wird der Einlass der Termine ausgegeben (Standard). */
    public function withAdmission(): bool
    {
        return $this->boolean('with_admission', true);
    }
}

<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Auftritt des Projekts im Ticketshop; das Bild kommt als Datei mit, remove_hero nimmt es weg. */
class SaveTicketingProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Die Auswahl kommt als JSON-String im Formular mit, damit auch "keine" ankommt. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reduction_type_ids'))) {
            $this->merge(['reduction_type_ids' => json_decode($this->input('reduction_type_ids'), true)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:4000',
            'reduction_type_ids' => 'nullable|array|max:50',
            'reduction_type_ids.*' => 'required|uuid',
            'hero' => 'nullable|image|max:8192',
            'remove_hero' => 'sometimes|boolean',
        ];
    }
}

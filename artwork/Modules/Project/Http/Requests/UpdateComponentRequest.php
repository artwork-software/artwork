<?php

namespace Artwork\Modules\Project\Http\Requests;

/**
 * Beim Ändern bleibt der Typ fest (das Frontend sendet ihn nur beim Anlegen); der Controller
 * übernimmt nur name/data/permission_type.
 */
class UpdateComponentRequest extends StoreComponentRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        unset($rules['type']);

        return $rules;
    }
}

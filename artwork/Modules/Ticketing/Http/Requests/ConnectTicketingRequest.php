<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Der Entwurf aus dem Verbindungs-Assistenten: Haus, Räume mit Preisklassen, Ermäßigungen. */
class ConnectTicketingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Berechtigung liegt auf der Routengruppe (manage ticketing).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'house.name' => 'required|string|min:2|max:120',
            'house.slug' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
            ...TicketingDraftRules::rooms(),
            ...TicketingDraftRules::reductions(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => TicketingDraftRules::checkReductions(
            $validator,
            $this->input('reductions', []),
        ));
    }
}

<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Der Entwurf aus dem Verbindungs-Assistenten: Haus, Angaben (oder null), Räume mit Preisklassen, Ermäßigungen. */
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
            // Übersprungen kommt der Block als null; sonst gilt er ganz.
            'billing' => 'present|nullable|array',
            ...($this->input('billing') === null ? [] : TicketingDraftRules::billing()),
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

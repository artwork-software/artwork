<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Der Tab "Angaben & Bankverbindung": jedes Feld darf leer bleiben, wie in den Einstellungen von
 * tickets selbst — ob die Blöcke vollständig sind, sagt tickets nach dem Speichern.
 */
class SaveTicketingBillingRequest extends FormRequest
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
            'legal_name' => 'present|nullable|string|max:200',
            'legal_form' => ['present', 'nullable', Rule::in(TicketingDraftRules::LEGAL_FORMS)],
            'street' => 'present|nullable|string|max:200',
            'postal_code' => 'present|nullable|string|max:12',
            'city' => 'present|nullable|string|max:120',
            'country' => ['required', Rule::in(TicketingDraftRules::COUNTRIES)],
            'register_number' => 'present|nullable|string|max:60',
            'register_court' => 'present|nullable|string|max:120',
            'vat_id' => 'present|nullable|string|max:20',
            'tax_number' => 'present|nullable|string|max:30',
            'contact_name' => 'present|nullable|string|max:160',
            'contact_phone' => 'present|nullable|string|max:40',
            'website' => 'present|nullable|url|max:200',
            'account_holder' => 'present|nullable|string|max:160',
            'iban' => ['present', 'nullable', 'string', TicketingDraftRules::ibanRule()],
        ];
    }
}

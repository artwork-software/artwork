<?php

namespace Artwork\Modules\Contacts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    /**
     * Autorisierung VOR der Validierung, sonst antwortet ein fehlendes Recht mit 422 statt 403.
     */
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof \Artwork\Modules\Contacts\Models\Contact
            && ($this->user()?->can('update', $contact) ?? false);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:contacts,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:100'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],

            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}

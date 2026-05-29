<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelinkAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['new_crm_contact_id' => ['required', 'integer', 'exists:crm_contacts,id']];
    }
}

<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiEventDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Obergrenzen passend zu den Spalten (unsigned int bzw. decimal(12,2)), sonst 500er beim Speichern
            'visitors' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'sold_tickets' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'revenue' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'scope' => ['nullable', 'in:actual,plan'],
        ];
    }
}

<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Core\Validation\Rules\IconNameRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftQualificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'icon' => ['string', 'max:255', new IconNameRule()],
            'name' => 'string|max:255',
            'available' => 'boolean'
        ];
    }
}

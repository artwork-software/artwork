<?php

namespace Artwork\Modules\ShiftQualification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftQualificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'icon' => 'string',
            'name' => 'string',
            'available' => 'boolean'
        ];
    }
}

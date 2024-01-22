<?php

namespace Artwork\Modules\ShiftQualification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        //see Artwork\Modules\ShiftQualification\Policies\ShiftQualificationPolicy
        return true;
    }

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

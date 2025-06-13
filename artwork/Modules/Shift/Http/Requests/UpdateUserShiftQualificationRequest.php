<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserShiftQualificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shiftQualificationId' => 'int|exists:shift_qualifications,id',
            'create' => 'boolean'
        ];
    }
}

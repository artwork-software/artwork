<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateViolationStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:resolved,ignored',
        ];
    }
}

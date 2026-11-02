<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppPassAdmissionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'admission_id' => ['required', 'uuid'],
            'pass_id' => ['required', 'uuid'],
            'price_category_id' => ['required', 'uuid'],
        ];
    }
}

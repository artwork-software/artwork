<?php

namespace Artwork\Modules\SageApiSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrUpdateSageApiSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'host' => 'string',
            'endpoint' => 'string',
            'user' => 'string',
            'password' => 'string',
            'bookingDate' => 'date|nullable',
            'fetchTime' => 'string|nullable',
            'enabled' => 'boolean'
        ];
    }
}

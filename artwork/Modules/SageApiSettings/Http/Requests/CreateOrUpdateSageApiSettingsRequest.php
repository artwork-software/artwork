<?php

namespace Artwork\Modules\SageApiSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrUpdateSageApiSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

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

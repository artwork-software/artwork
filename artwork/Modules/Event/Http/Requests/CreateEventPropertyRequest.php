<?php

namespace Artwork\Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEventPropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'icon' => 'required|string',
            'name' => 'required|string'
        ];
    }
}

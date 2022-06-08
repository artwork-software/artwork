<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['string', 'max:15'],
            'position' => ['required', 'string', 'max:255'],
            'business' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'max:5000'],
            'role' => ['sometimes', 'string'],
            'permissions' => ['sometimes','array']
        ];
    }
}

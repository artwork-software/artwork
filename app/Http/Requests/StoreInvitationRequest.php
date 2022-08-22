<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
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
            'user_emails' => 'required|array',
            'permissions' => 'array',
            'role' => 'sometimes',
            'user_emails.*' => 'email|unique:users,email'
        ];
    }
}

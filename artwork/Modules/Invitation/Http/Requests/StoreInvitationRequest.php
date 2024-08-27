<?php

namespace Artwork\Modules\Invitation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'user_emails' => 'required|array',
            'permissions' => 'array',
            'role' => 'sometimes',
            'user_emails.*' => 'email|unique:users,email'
        ];
    }
}

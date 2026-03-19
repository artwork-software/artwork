<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUsersToRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ];
    }
}

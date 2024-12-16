<?php

namespace Artwork\Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomUserRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            '*.id' => 'required|exists:users,id',
            '*.is_admin' => 'required|boolean',
            '*.can_request' => 'required|boolean',
        ];
    }
}

<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppRevertCheckInRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'uuid'],
        ];
    }
}

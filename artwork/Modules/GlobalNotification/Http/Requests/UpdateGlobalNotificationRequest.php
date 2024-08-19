<?php

namespace Artwork\Modules\GlobalNotification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalNotificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notificationName' => 'required|string',
            'notificationDeadlineDate' => 'string|nullable',
            'notificationDeadlineTime' => 'string|nullable',
            'notificationDescription' => 'string|nullable',
        ];
    }
}

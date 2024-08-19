<?php

namespace Artwork\Modules\GlobalNotification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalNotificationImageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notificationImage' => 'required|string',
        ];
    }
}

<?php

namespace Artwork\Modules\GlobalNotification\Http\Requests;

use Artwork\Core\FileHandling\Upload\SafeUploadFile;
use Illuminate\Foundation\Http\FormRequest;

class StoreGlobalNotificationRequest extends FormRequest
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
            'notificationImage' => ['nullable', new SafeUploadFile()],
        ];
    }
}

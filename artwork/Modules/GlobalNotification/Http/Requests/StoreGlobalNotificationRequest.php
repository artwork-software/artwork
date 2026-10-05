<?php

namespace Artwork\Modules\GlobalNotification\Http\Requests;

use Artwork\Core\FileHandling\Upload\SafeUploadFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // Landet auf der public-Disk: als Datei nur echte Bilder (Update schickt sonst die bestehende URL).
            'notificationImage' => [
                'nullable',
                Rule::when($this->hasFile('notificationImage'), ['image', 'max:10240']),
                new SafeUploadFile(),
            ],
        ];
    }
}

<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notification_types' => ['required', 'array', 'min:1'],
            'notification_types.*' => ['required', Rule::enum(NotificationEnum::class)],
        ];
    }
}

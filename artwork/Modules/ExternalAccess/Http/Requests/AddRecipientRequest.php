<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddRecipientRequest extends FormRequest
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
            'recipient_type' => ['required', Rule::in(['User', 'Department'])],
            'recipient_id' => ['required', 'integer', 'min:1'],
            'notification_types' => ['required', 'array', 'min:1'],
            'notification_types.*' => ['required', Rule::enum(NotificationEnum::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $modelClass = $this->input('recipient_type') === 'User' ? User::class : Department::class;

            $exists = ExternalAccessNotificationRecipient::query()
                ->where('recipient_type', $modelClass)
                ->where('recipient_id', $this->input('recipient_id'))
                ->exists();

            if ($exists) {
                $v->errors()->add(
                    'recipient_id',
                    __('This recipient is already configured. Update the existing entry instead.'),
                );
            }
        });
    }
}

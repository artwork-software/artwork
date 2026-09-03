<?php

namespace Artwork\Modules\Invitation\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_emails' => 'required|array',
            'user_emails.*' => 'email|unique:users,email',
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::enum(PermissionEnum::class)],
            'roles' => 'array',
            'roles.*' => [
                'string',
                Rule::enum(RoleEnum::class),
                // Die Admin-Rolle darf nur vergeben, wer selbst artwork-Admin ist – "Personalverwaltung"
                // reicht zum Einladen, aber nicht zum Anlegen weiterer Admins.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (
                        $value === RoleEnum::ARTWORK_ADMIN->value
                        && !$this->user()?->hasRole(RoleEnum::ARTWORK_ADMIN->value)
                    ) {
                        $fail(__('Only artwork admins can assign the admin role.'));
                    }
                },
            ],
        ];
    }
}

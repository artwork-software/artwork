<?php

namespace Artwork\Modules\Invitation\Http\Requests;

use Artwork\Core\Validation\Rules\NotReservedUserEmail;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
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
            'user_emails.*' => [
                'bail',
                'email',
                // Deaktivierte Accounts reaktivieren statt neu einladen – eigene Meldung statt "bereits vergeben".
                // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (User::query()->where('email', $value)->deactivated()->exists()) {
                        $fail(__('There is a deactivated account for this email address. Reactivate it in the user list.'));
                    }
                },
                'unique:users,email',
                new NotReservedUserEmail(),
            ],
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::enum(PermissionEnum::class)],
            'roles' => 'array',
            'roles.*' => [
                'string',
                Rule::enum(RoleEnum::class),
                // Die Admin-Rolle darf nur vergeben, wer selbst artwork-Admin ist – "Personalverwaltung"
                // reicht zum Einladen, aber nicht zum Anlegen weiterer Admins.
                // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
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

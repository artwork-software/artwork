<?php

namespace Artwork\Modules\Invitation\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
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
            'roles.*' => ['string', Rule::enum(RoleEnum::class)],
        ];
    }
}

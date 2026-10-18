<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Spatie\Permission\Models\Role;

/**
 * Gruppen-Mappings und Default-Rollen von Verzeichnisquellen vergeben beim Sync/Login Rollen und
 * Rechte an Verzeichnis-Konten. Die Admin-Rolle darf nur zuordnen, wer selbst artwork-Admin ist –
 * „Tool-Einstellungen ändern“ reicht dafür nicht (sonst: eigene Verzeichnisgruppe → Admin-Rolle →
 * Rechteausweitung). Rechte dürfen Nicht-Admins nur zuordnen, wenn sie sie selbst besitzen.
 * Wie beim Einladen (InvitationService::filterGrantablePermissions).
 */
trait RestrictsAdminRoleMapping
{
    protected function adminRoleRule(): Closure
    {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
        return function (string $attribute, mixed $value, Closure $fail): void {
            $adminRoleId = Role::query()->where('name', RoleEnum::ARTWORK_ADMIN->value)->value('id');
            if (
                $adminRoleId !== null
                && (int) $value === (int) $adminRoleId
                && !$this->user()?->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            ) {
                $fail(__('Only artwork admins can assign the admin role.'));
            }
        };
    }

    /**
     * Nicht-Admins dürfen einer Verzeichnisgruppe nur Rechte zuordnen, die sie selbst besitzen
     * (direkt oder über Rollen) – sonst gäbe man der eigenen Gruppe beliebige Rechte.
     */
    protected function grantablePermissionRule(): Closure
    {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = $this->user();

            if ($user === null) {
                $fail(__('You can only map permissions that you hold yourself.'));

                return;
            }

            if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
                return;
            }

            $heldPermissionIds = $user->getAllPermissions()->pluck('id')->map(fn ($id): int => (int) $id);

            if (!$heldPermissionIds->contains((int) $value)) {
                $fail(__('You can only map permissions that you hold yourself.'));
            }
        };
    }
}

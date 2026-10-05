<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Spatie\Permission\Models\Role;

/**
 * Gruppen-Mappings vergeben beim Sync Rollen an Verzeichnis-Konten. Die Admin-Rolle darf nur
 * zuordnen, wer selbst artwork-Admin ist – „Tool-Einstellungen ändern“ reicht dafür nicht
 * (sonst: eigene Verzeichnisgruppe → Admin-Rolle → Rechteausweitung). Wie beim Einladen.
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
}

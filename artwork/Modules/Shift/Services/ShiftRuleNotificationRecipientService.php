<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Empfänger*innen von Regelverstoß-Benachrichtigungen (ShiftRuleNotificationAction): nur Personen,
 * die den Dienstplan sehen oder planen dürfen (can view shift plan / can plan shifts) oder Admin sind.
 * Die Notification verlinkt in den Dienstplan — ohne Sichtrecht wäre der Link tot und die Meldung
 * ein Informationsabfluss. Gleiche Menge für die Auswahlliste „Benachrichtigen" im Regel-Dialog.
 */
class ShiftRuleNotificationRecipientService
{
    /**
     * @return array<int, string>
     */
    public static function requiredPermissions(): array
    {
        return [
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::SHIFT_PLANNER->value,
        ];
    }

    public function isEligible(User $user): bool
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }

        foreach (self::requiredPermissions() as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Collection<int, User> $users
     * @return Collection<int, User>
     */
    public function filter(Collection $users): Collection
    {
        return $users->filter(fn (User $user): bool => $this->isEligible($user))->values();
    }

    /**
     * Query über alle berechtigten Personen (direkte Rechte, Rechte über Rollen, Admin-Rolle).
     * Bewusst über Relationen statt Spatie-Scope permission(), der bei unbekanntem Rechtenamen wirft.
     *
     * @return Builder<User>
     */
    public function eligibleUsersQuery(): Builder
    {
        $permissions = self::requiredPermissions();

        return User::query()->where(static function (Builder $query) use ($permissions): void {
            $query
                ->whereHas('roles', static fn ($roles) => $roles->where('name', RoleEnum::ARTWORK_ADMIN->value))
                ->orWhereHas('permissions', static fn ($direct) => $direct->whereIn('name', $permissions))
                ->orWhereHas('roles.permissions', static fn ($viaRole) => $viaRole->whereIn('name', $permissions));
        });
    }
}

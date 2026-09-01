<?php

namespace Artwork\Modules\User\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    use HandlesAuthorization;


    public function viewAny(): bool
    {
        return true;
    }


    public function view(): bool
    {
        return true;
    }
    public function update(User $user): bool
    {
        return Auth::user()->id === $user->id;
    }

    public function updateOwnPreferences(User $user, User $targetUser): bool
    {
        return $user->is($targetUser);
    }

    /**
     * Rechte, die den Blick auf FREMDE Einsatzpläne erlauben — wer den Dienstplan
     * ohnehin sehen oder planen darf, darf auch die Einzel-Einsatzpläne öffnen.
     * Eine Quelle für User-/Freelancer-/Dienstleister-Pläne und den PDF-Export;
     * Frontend-Spiegel: canViewForeignRoster() in Composeables/Permission.js.
     */
    public const FOREIGN_ROSTER_PERMISSIONS = [
        PermissionEnum::SHIFT_PLANNER,
        PermissionEnum::MA_MANAGER,
        PermissionEnum::VIEW_SHIFT_PLAN,
    ];

    public function viewOperationPlan(User $user, User $targetUser): bool
    {
        // Eigener Einsatzplan nur mit "can view own roster" — wer fremde Pläne sehen
        // darf, sieht auch den eigenen (Dienstplan-Sichtrechte schließen ihn ein).
        if ($user->is($targetUser) && $user->can(PermissionEnum::CAN_VIEW_OWN_ROSTER->value)) {
            return true;
        }

        return self::canViewForeignRoster($user);
    }

    public static function canViewForeignRoster(User $user): bool
    {
        // canAny() läuft pro Recht durchs Gate — Admins passieren via Gate::before.
        return $user->canAny(
            array_map(static fn (PermissionEnum $permission) => $permission->value, self::FOREIGN_ROSTER_PERMISSIONS)
        );
    }

    /**
     * Freelancer-/Dienstleister-PROFILSEITEN (freelancer.show/service_provider.show):
     * zusätzlich zu den Dienstplan-Sichtrechten öffnet auch "can view private user info" —
     * für Endnutzer sind Freelancer/Dienstleister Teil der Nutzer*innenverwaltung, deren
     * Kontaktdaten dieses Recht sichtbar macht. Reine Einsatzplan-Endpunkte (PDF-Export,
     * user.edit.shiftplan) bleiben bewusst Roster-Rechten vorbehalten.
     * Frontend-Spiegel: canViewExternalWorkerProfile() in Composeables/Permission.js.
     */
    public static function canViewExternalWorkerProfile(User $user): bool
    {
        return self::canViewForeignRoster($user)
            || $user->can(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function updateWorkProfile(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }

    public function updateTerms(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }
}

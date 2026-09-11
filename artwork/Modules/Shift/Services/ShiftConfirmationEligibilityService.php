<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Einzige Quelle für „nimmt diese Person am Bestätigungs-Flow (Zu-/Absage) teil?".
 *
 * Teilnahme = User mit dem Recht „Darf Schichten annehmen/ablehnen" (direkt oder über
 * eine Rolle). Freelancer und Dienstleister können kein Recht tragen und nehmen deshalb
 * nicht teil; Admins sind NICHT implizit dabei (Opt-in je Person, kein Gate::before).
 *
 * Die berechtigten User-IDs werden einmal je Request ermittelt (scoped Binding), damit
 * die Worker-Serialisierungen (Kalender, Listenansicht, Matrix, Einsatzplan) ohne
 * Query je Person auskommen.
 */
class ShiftConfirmationEligibilityService
{
    /** @var array<int, true>|null */
    private ?array $eligibleUserIds = null;

    public function isEligible(mixed $worker): bool
    {
        if (!$worker instanceof User) {
            return false;
        }

        return $this->isEligibleUserId((int) $worker->getKey());
    }

    public function isEligibleUserId(int $userId): bool
    {
        return isset($this->eligibleUserIds()[$userId]);
    }

    public function isEligiblePivot(ShiftWorker $pivot): bool
    {
        return $pivot->employable_type === User::class
            && $this->isEligibleUserId((int) $pivot->employable_id);
    }

    /** @return array<int, true> */
    private function eligibleUserIds(): array
    {
        if ($this->eligibleUserIds !== null) {
            return $this->eligibleUserIds;
        }

        try {
            // Spatie-Scope: direkte Rechte UND Rechte über Rollen
            $ids = User::query()
                ->permission(PermissionEnum::CAN_RESPOND_TO_SHIFT_ASSIGNMENTS->value)
                ->pluck('users.id')
                ->all();
        } catch (PermissionDoesNotExist) {
            // Recht noch nicht angelegt (artwork:update-permissions ausstehend) → niemand nimmt teil
            $ids = [];
        }

        return $this->eligibleUserIds = array_fill_keys(array_map('intval', $ids), true);
    }
}

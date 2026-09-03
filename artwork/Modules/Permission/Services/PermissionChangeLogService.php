<?php

namespace Artwork\Modules\Permission\Services;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Änderungsverlauf der Nutzerrechte: wer hat wann welche Rechte/Rollen vergeben oder entzogen.
 * Schreibt ins Spatie-Activity-Log (log_name "permissions", subject = betroffene Person).
 */
class PermissionChangeLogService
{
    public const LOG_NAME = 'permissions';
    public const EVENT = 'permissions_changed';

    /**
     * @param string[] $permissionsBefore
     * @param string[] $permissionsAfter
     * @param string[] $rolesBefore
     * @param string[] $rolesAfter
     */
    public function log(
        User $subject,
        ?User $causer,
        array $permissionsBefore,
        array $permissionsAfter,
        array $rolesBefore,
        array $rolesAfter,
        ?string $source = null,
    ): void {
        $added = array_values(array_diff($permissionsAfter, $permissionsBefore));
        $removed = array_values(array_diff($permissionsBefore, $permissionsAfter));
        $rolesAdded = array_values(array_diff($rolesAfter, $rolesBefore));
        $rolesRemoved = array_values(array_diff($rolesBefore, $rolesAfter));

        if ($added === [] && $removed === [] && $rolesAdded === [] && $rolesRemoved === []) {
            return;
        }

        $activity = activity()
            ->performedOn($subject)
            ->useLog(self::LOG_NAME)
            ->event(self::EVENT)
            ->withProperties([
                'added' => $added,
                'removed' => $removed,
                'roles_added' => $rolesAdded,
                'roles_removed' => $rolesRemoved,
                'source' => $source,
            ]);

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        $activity->log('permissions changed');
    }

    /**
     * Verlauf einer Person, neueste zuerst.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function historyFor(User $subject, int $limit = 30): Collection
    {
        return Activity::query()
            ->where('log_name', self::LOG_NAME)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(static function (Activity $activity): array {
                $causer = $activity->causer;

                return [
                    'id' => $activity->id,
                    'at' => $activity->created_at?->toIso8601String(),
                    'causer' => $causer instanceof User
                        ? ['id' => $causer->id, 'name' => trim($causer->first_name . ' ' . $causer->last_name)]
                        : null,
                    'added' => $activity->properties['added'] ?? [],
                    'removed' => $activity->properties['removed'] ?? [],
                    'roles_added' => $activity->properties['roles_added'] ?? [],
                    'roles_removed' => $activity->properties['roles_removed'] ?? [],
                    'source' => $activity->properties['source'] ?? null,
                ];
            })
            ->values();
    }
}

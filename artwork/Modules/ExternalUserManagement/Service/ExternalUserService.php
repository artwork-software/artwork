<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Repository\ExternalUserRepository;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Spatie\Permission\Models\Role;

class ExternalUserService
{
    public function __construct(
        private readonly ExternalUserRepository $externalUserRepository,
        private readonly UserRepository $userRepository,
        private readonly IdentityResolutionService $identityResolutionService
    ) {
    }

    /**
     * Löst die extern authentifizierte Identität über den zentralen
     * {@see IdentityResolutionService} auf (Subject+Issuer → E-Mail-Erstverknüpfung
     * → Provisionierung). Wird sowohl vom interaktiven OIDC-Login als auch vom
     * LDAP-Batch-Sync genutzt.
     */
    public function findOrCreateUser(
        ExternalUserSource $source,
        array $externalUserData,
        string $identifier
    ): User {
        return $this->identityResolutionService->resolveAndLink(
            $source,
            $identifier,
            $externalUserData['email'] ?? null,
            (bool) ($externalUserData['email_verified'] ?? false),
            [
                'first_name' => $externalUserData['first_name'] ?? '',
                'last_name' => $externalUserData['last_name'] ?? '',
            ]
        );
    }

    /**
     * Gleicht Rechte und Rollen aus den Gruppen-Mappings der Quelle ab. Der Sync entzieht nur, was
     * er selbst vergeben hat (meta_data synced_*): Rechte/Rollen, die eine Person schon von Hand
     * hatte, bleiben – auch wenn ein Mapping sie ebenfalls liefert und dann gelöscht wird oder die
     * Person die Gruppe verlässt. Rechte aus gelöschten oder geänderten Mappings verschwinden weiter.
     */
    public function syncUserGroups(
        ExternalUserSource $source,
        User $user,
        array $userGroups,
        ExternalUserGroupMappingService $groupMappingService
    ): void {
        $groupMappings = $groupMappingService->getAllBySourceId($source->id);
        $externalUser = $this->externalUserRepository->findBySourceIdAndUserId($source->id, $user->id);
        $previousMetaData = $externalUser?->meta_data ?? [];

        $activeMappings = $groupMappings->filter(
            fn ($mapping): bool => in_array($mapping->ad_group_dn, $userGroups, true)
        );
        $idsOf = fn ($mappings, string $key) => $mappings
            ->flatMap(fn ($mapping): array => $mapping->{$key} ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $activePermissionIds = $idsOf($activeMappings, 'permission_ids');
        $ownedPermissionIds = $this->ownedBySync(
            $previousMetaData,
            'synced_permission_ids',
            $idsOf($groupMappings, 'permission_ids')
        );
        $heldPermissionIds = $user->getDirectPermissions()->pluck('id')->map(fn ($id): int => (int) $id);

        $activeRoleIds = $idsOf($activeMappings, 'role_ids');
        $ownedRoleIds = $this->ownedBySync($previousMetaData, 'synced_role_ids', $idsOf($groupMappings, 'role_ids'));
        $heldRoleIds = $user->roles()->pluck('id')->map(fn ($id): int => (int) $id);

        $revokePermissionIds = $ownedPermissionIds->diff($activePermissionIds);
        $revokeRoleIds = $ownedRoleIds->diff($activeRoleIds);

        $permissions = Permission::whereIn('id', $activePermissionIds->merge($revokePermissionIds))
            ->pluck('name', 'id');
        foreach ($activePermissionIds as $permissionId) {
            if ($permissions->has($permissionId)) {
                $user->givePermissionTo($permissions->get($permissionId));
            }
        }
        foreach ($revokePermissionIds as $permissionId) {
            if ($permissions->has($permissionId)) {
                $user->revokePermissionTo($permissions->get($permissionId));
            }
        }

        $roles = Role::whereIn('id', $activeRoleIds->merge($revokeRoleIds))->pluck('name', 'id');
        foreach ($activeRoleIds as $roleId) {
            if ($roles->has($roleId)) {
                $user->assignRole($roles->get($roleId));
            }
        }
        foreach ($revokeRoleIds as $roleId) {
            if ($roles->has($roleId)) {
                $user->removeRole($roles->get($roleId));
            }
        }

        if ($externalUser) {
            $metaData = $previousMetaData;
            $metaData['security_groups'] = $userGroups;
            // Besitz des Syncs: was er weiter liefert und schon besaß, plus was er gerade neu vergeben
            // hat. Von Hand Vorhandenes wird nie Besitz des Syncs.
            $metaData['synced_permission_ids'] = $ownedPermissionIds->intersect($activePermissionIds)
                ->merge($activePermissionIds->diff($heldPermissionIds))
                ->unique()->values()->all();
            $metaData['synced_role_ids'] = $ownedRoleIds->intersect($activeRoleIds)
                ->merge($activeRoleIds->diff($heldRoleIds))
                ->unique()->values()->all();
            $this->externalUserRepository->update($externalUser, ['meta_data' => $metaData]);
        }
    }

    /**
     * Was der Sync bei dieser Person vergeben hat. Ohne Aufzeichnung: Konten, die schon unter der
     * alten Logik synchronisiert wurden (security_groups vorhanden), behandeln wie bisher alle
     * gemappten Rechte als Sync-Rechte; beim ersten Sync gilt alles Vorhandene als von Hand vergeben.
     *
     * @param array<string, mixed> $metaData
     * @param \Illuminate\Support\Collection<int, int> $mappedIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function ownedBySync(array $metaData, string $key, \Illuminate\Support\Collection $mappedIds): \Illuminate\Support\Collection
    {
        if (array_key_exists($key, $metaData)) {
            return collect($metaData[$key] ?? [])->map(fn ($id): int => (int) $id)->values();
        }

        return array_key_exists('security_groups', $metaData) ? $mappedIds : collect();
    }

    public function findOrCreateExternalUser(
        ExternalUserSource $source,
        string $identifier,
        array $metaData,
        ?User $user = null
    ): ExternalUser {
        $externalUser = $this->externalUserRepository->findOrCreateBySourceAndIdentification(
            $source->id,
            $identifier,
            ['meta_data' => $metaData]
        );

        if ($user) {
            $this->externalUserRepository->update($externalUser, ['user_id' => $user->id]);
        }

        return $externalUser;
    }
}

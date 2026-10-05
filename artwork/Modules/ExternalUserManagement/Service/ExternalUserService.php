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
     * Gleicht Rechte und Rollen aus den Gruppen-Mappings der Quelle ab. Entzogen wird alles,
     * was ein Mapping vergeben könnte, aber gerade nicht zutrifft – UND alles, was der Sync
     * dieser Person früher vergeben hat und heute kein Mapping mehr liefert. Ohne Letzteres
     * blieben Rechte aus gelöschten oder geänderten Mappings für immer bestehen.
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
        $previouslyGrantedPermissionIds = collect($previousMetaData['synced_permission_ids'] ?? [])
            ->map(fn ($id): int => (int) $id);
        $previouslyGrantedRoleIds = collect($previousMetaData['synced_role_ids'] ?? [])
            ->map(fn ($id): int => (int) $id);

        $activeMappings = $groupMappings->filter(
            fn ($mapping): bool => in_array($mapping->ad_group_dn, $userGroups, true)
        );
        $mappedPermissionIds = $groupMappings->flatMap(
            fn ($mapping): array => $mapping->permission_ids ?? []
        )->map(fn ($id): int => (int) $id)->unique()->values();
        $activePermissionIds = $activeMappings->flatMap(
            fn ($mapping): array => $mapping->permission_ids ?? []
        )->map(fn ($id): int => (int) $id)->unique()->values();
        $mappedRoleIds = $groupMappings->flatMap(
            fn ($mapping): array => $mapping->role_ids ?? []
        )->map(fn ($id): int => (int) $id)->unique()->values();
        $activeRoleIds = $activeMappings->flatMap(
            fn ($mapping): array => $mapping->role_ids ?? []
        )->map(fn ($id): int => (int) $id)->unique()->values();

        $revokePermissionIds = $mappedPermissionIds->merge($previouslyGrantedPermissionIds)
            ->unique()
            ->diff($activePermissionIds);
        $revokeRoleIds = $mappedRoleIds->merge($previouslyGrantedRoleIds)
            ->unique()
            ->diff($activeRoleIds);

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
            // Merken, was der Sync vergeben hat – Grundlage für das Entziehen beim nächsten Lauf
            $metaData['synced_permission_ids'] = $activePermissionIds->all();
            $metaData['synced_role_ids'] = $activeRoleIds->all();
            $this->externalUserRepository->update($externalUser, ['meta_data' => $metaData]);
        }
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

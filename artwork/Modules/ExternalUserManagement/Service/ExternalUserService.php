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

    public function syncUserGroups(
        ExternalUserSource $source,
        User $user,
        array $userGroups,
        ExternalUserGroupMappingService $groupMappingService
    ): void {
        $groupMappings = $groupMappingService->getAllBySourceId($source->id);

        $activeMappings = $groupMappings->filter(
            fn ($mapping): bool => in_array($mapping->ad_group_dn, $userGroups, true)
        );
        $mappedPermissionIds = $groupMappings->flatMap(
            fn ($mapping): array => $mapping->permission_ids ?? []
        )->unique()->values();
        $activePermissionIds = $activeMappings->flatMap(
            fn ($mapping): array => $mapping->permission_ids ?? []
        )->unique()->values();
        $mappedRoleIds = $groupMappings->flatMap(
            fn ($mapping): array => $mapping->role_ids ?? []
        )->unique()->values();
        $activeRoleIds = $activeMappings->flatMap(
            fn ($mapping): array => $mapping->role_ids ?? []
        )->unique()->values();

        $permissions = Permission::whereIn('id', $mappedPermissionIds)->pluck('name', 'id');
        foreach ($activePermissionIds as $permissionId) {
            if ($permissions->has($permissionId)) {
                $user->givePermissionTo($permissions->get($permissionId));
            }
        }
        foreach ($mappedPermissionIds->diff($activePermissionIds) as $permissionId) {
            if ($permissions->has($permissionId)) {
                $user->revokePermissionTo($permissions->get($permissionId));
            }
        }

        $roles = Role::whereIn('id', $mappedRoleIds)->pluck('name', 'id');
        foreach ($activeRoleIds as $roleId) {
            if ($roles->has($roleId)) {
                $user->assignRole($roles->get($roleId));
            }
        }
        foreach ($mappedRoleIds->diff($activeRoleIds) as $roleId) {
            if ($roles->has($roleId)) {
                $user->removeRole($roles->get($roleId));
            }
        }

        $externalUser = $this->externalUserRepository->findBySourceIdAndUserId($source->id, $user->id);

        if ($externalUser) {
            $metaData = $externalUser->meta_data ?? [];
            $metaData['security_groups'] = $userGroups;
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

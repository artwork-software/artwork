<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Repository\ExternalUserRepository;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserGroupMappingService;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ExternalUserService
{
    public function __construct(
        private readonly ExternalUserRepository $externalUserRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Findet oder erstellt einen User basierend auf AD-Identifier oder Email
     */
    public function findOrCreateUser(array $ldapUser, string $identifier): User
    {
        $email = $ldapUser['email'] ?? null;
        $firstName = $ldapUser['first_name'] ?? '';
        $lastName = $ldapUser['last_name'] ?? '';

        $user = $this->userRepository->getNewModelQuery()
            ->where('ad_identifier', $identifier)
            ->orWhere(function ($query) use ($email) {
                if ($email) {
                    $query->where('email', $email);
                }
            })
            ->first();

        if (!$user) {
            if (!$email) {
                throw new \InvalidArgumentException("Cannot create user without email address");
            }

            /** @var User $user */
            $user = $this->userRepository->getNewModelInstance();
            $user->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make(uniqid('', true)), // Temporäres Passwort
                'ad_managed' => true,
                'ad_identifier' => $identifier,
            ]);
            $this->userRepository->save($user);
        } elseif ($user->ad_managed) {
            // Update nur wenn ad_managed=true
            $user->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'ad_identifier' => $identifier,
            ]);
            $this->userRepository->save($user);
        }

        return $user;
    }

    /**
     * Synchronisiert Gruppenmitgliedschaften und Rechte für einen User
     */
    public function syncUserGroups(
        ExternalUserSource $source,
        User $user,
        array $userGroups,
        ExternalUserGroupMappingService $groupMappingService
    ): void {
        $groupMappings = $groupMappingService->getAllBySourceId($source->id);

        foreach ($groupMappings as $mapping) {
            $groupDn = $mapping->ad_group_dn;
            $isMember = in_array($groupDn, $userGroups);

            if ($isMember) {
                // Weise Permissions zu
                if (!empty($mapping->permission_ids)) {
                    $permissions = Permission::whereIn('id', $mapping->permission_ids)->pluck('name');
                    $user->givePermissionTo($permissions);
                }

                // Weise Roles zu
                if (!empty($mapping->role_ids)) {
                    $roles = Role::whereIn('id', $mapping->role_ids)->pluck('name');
                    $user->assignRole($roles);
                }
            } else {
                // Entferne Permissions/Roles wenn User nicht mehr in Gruppe ist
                if (!empty($mapping->permission_ids)) {
                    $permissions = Permission::whereIn('id', $mapping->permission_ids)->pluck('name');
                    $user->revokePermissionTo($permissions);
                }
            }
        }

        // Speichere Gruppen in meta_data des ExternalUser
        $externalUser = $this->externalUserRepository->findBySourceIdAndUserId($source->id, $user->id);

        if ($externalUser) {
            $metaData = $externalUser->meta_data ?? [];
            $metaData['security_groups'] = $userGroups;
            $this->externalUserRepository->update($externalUser, ['meta_data' => $metaData]);
        }
    }

    /**
     * Findet oder erstellt einen ExternalUser und verknüpft ihn mit einem User
     */
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


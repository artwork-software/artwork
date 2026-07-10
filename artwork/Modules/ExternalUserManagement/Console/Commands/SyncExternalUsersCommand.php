<?php

namespace Artwork\Modules\ExternalUserManagement\Console\Commands;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserGroupMappingService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncExternalUsersCommand extends Command
{
    protected $signature = 'external-users:sync {--source-id= : Sync only specific source}';

    protected $description = 'Synchronize external users from LDAP/AD sources (runs every 30 minutes)';

    public function handle(
        LdapService $ldapService,
        ExternalUserSourceService $externalUserSourceService,
        ExternalUserService $externalUserService,
        ExternalUserGroupMappingService $groupMappingService
    ): int {
        $this->info('Starting external user synchronization...');

        $sourceId = $this->option('source-id');
        $sources = $sourceId
            ? $externalUserSourceService->getAllActiveLdapSources()->where('id', $sourceId)
            : $externalUserSourceService->getAllActiveLdapSources();

        if ($sources->isEmpty()) {
            $this->warn('No active LDAP sources found.');
            return Command::SUCCESS;
        }

        foreach ($sources as $source) {
            $this->info("Syncing source: {$source->name} (ID: {$source->id})");
            $this->syncSource($source, $ldapService, $externalUserService, $groupMappingService);
        }

        $this->info('External user synchronization completed.');
        return Command::SUCCESS;
    }

    private function syncSource(
        ExternalUserSource $source,
        LdapService $ldapService,
        ExternalUserService $externalUserService,
        ExternalUserGroupMappingService $groupMappingService
    ): void {
        $ldapUsers = $ldapService->fetchUsers($source);

        if ($ldapUsers->isEmpty()) {
            $this->warn("No users found for source: {$source->name}");
            return;
        }

        $this->info("Found {$ldapUsers->count()} users in LDAP");

        foreach ($ldapUsers as $ldapUser) {
            try {
                $this->syncUser($source, $ldapUser, $externalUserService, $groupMappingService);
            } catch (\InvalidArgumentException $e) {
                // Erwartete Exception: User ohne E-Mail wird übersprungen
                $this->warn("Skipping user {$ldapUser['identifier']}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully synced source: {$source->name}");
    }

    private function syncUser(
        ExternalUserSource $source,
        array $ldapUser,
        ExternalUserService $externalUserService,
        ExternalUserGroupMappingService $groupMappingService
    ): void {
        DB::transaction(function () use ($source, $ldapUser, $externalUserService, $groupMappingService) {
            $identifier = $ldapUser['identifier'];
            $email = $ldapUser['email'] ?? null;
            $groups = $ldapUser['groups'] ?? [];

            $user = $externalUserService->findOrCreateUser($source, $ldapUser, $identifier);

            if (!$user->ad_managed) {
                $this->warn("Skipping user {$email}: Not AD managed (ad_managed=false)");
                return;
            }

            $externalUserService->findOrCreateExternalUser(
                $source,
                $identifier,
                $ldapUser,
                $user
            );

            $externalUserService->syncUserGroups($source, $user, $groups, $groupMappingService);

            $this->line("Synced user: {$email}");
        });
    }

}

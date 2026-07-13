<?php

namespace Artwork\Modules\ExternalUserManagement\Console\Commands;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSyncService;
use Illuminate\Console\Command;

class SyncExternalUsersCommand extends Command
{
    protected $signature = 'external-users:sync {--source-id= : Sync only specific source}';

    protected $description = 'Synchronize external users from LDAP/AD sources (runs every 30 minutes)';

    public function handle(
        ExternalUserSourceService $externalUserSourceService,
        ExternalUserSyncService $syncService
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
            $syncService->syncSource($source, function (string $level, string $message): void {
                $this->{$level}($message);
            });
        }

        $this->info('External user synchronization completed.');
        return Command::SUCCESS;
    }
}

<?php

namespace Artwork\Modules\ExternalAccess\Console\Commands;

use Artwork\Modules\ExternalAccess\Repositories\ExternalLoginTokenRepository;
use Illuminate\Console\Command;

class CleanupExpiredLoginTokensCommand extends Command
{
    protected $signature = 'external:cleanup-tokens';

    protected $description = 'Delete expired or used external login tokens older than 24 hours.';

    public function handle(ExternalLoginTokenRepository $repository): int
    {
        $deleted = $repository->pruneExpiredAndUsed();

        $this->info(sprintf('Pruned %d external login token(s).', $deleted));

        return self::SUCCESS;
    }
}

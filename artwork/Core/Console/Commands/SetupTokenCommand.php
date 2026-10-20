<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Services\SetupTokenService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

/**
 * Erzeugt einen neuen Einmal-Token für /setup und gibt die Setup-URL aus. Der Docker-Entrypoint ruft
 * den Befehl bei jedem Start auf, solange das Setup offen ist; der Token steht dann im Container-Log.
 */
class SetupTokenCommand extends Command
{
    protected $signature = 'artwork:setup-token';

    protected $description = 'Generates a one-time token for the web setup (first administrator)';

    public function handle(SetupTokenService $setupTokenService): int
    {
        try {
            $setupFinished = (bool) app(GeneralSettings::class)->setup_finished;
        } catch (Throwable) {
            // Settings noch nicht migriert: Setup ist dann sicher noch offen.
            $setupFinished = false;
        }

        if ($setupFinished) {
            $setupTokenService->clear();
            $this->info('Setup is already finished, no setup token needed.');

            return CommandAlias::SUCCESS;
        }

        if ($setupTokenService->configuredToken() !== null) {
            $this->info('ARTWORK_SETUP_TOKEN is set. Open ' . route('setup') . ' and enter that value.');

            return CommandAlias::SUCCESS;
        }

        $token = $setupTokenService->regenerate();

        $this->warn('Setup is open. Create the first administrator via this URL:');
        $this->line($setupTokenService->setupUrl($token));

        return CommandAlias::SUCCESS;
    }
}

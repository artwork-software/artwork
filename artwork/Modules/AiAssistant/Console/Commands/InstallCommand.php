<?php

namespace Artwork\Modules\AiAssistant\Console\Commands;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * Legt den OAuth-Client an, ueber den KI-Session-Tokens ausgestellt werden (core-session-token).
 *
 * Der Client traegt bewusst kein Secret: Der ai_session-Grant prueft keines, und ein confidential
 * Client wuerde bei Integer-Client-IDs ueber Client::grantTypes() client_credentials fuehren —
 * TokenGuard.php:146 sperrt dann den User aus, dessen ID mit der Client-ID uebereinstimmt.
 *
 * Idempotent: Die Client-ID liegt in den Haus-Settings; ist sie gesetzt und der Client existiert,
 * passiert nichts.
 */
class InstallCommand extends Command
{
    protected $signature = 'ai:install';

    protected $description = 'Legt den OAuth-Client fuer KI-Session-Tokens an und hinterlegt seine ID.';

    public function handle(AiAssistantSettings $settings): int
    {
        $existing = $this->existingClient($settings);

        if ($existing !== null) {
            $this->info(sprintf('ai-session-Client existiert bereits. Client-ID: %s', $existing->getKey()));

            return self::SUCCESS;
        }

        $client = Passport::client()->newQuery()->forceCreate([
            'name' => 'ai-session',
            'secret' => null,
            'provider' => config('auth.guards.api.provider'),
            'redirect_uris' => [],
            'grant_types' => ['ai_session'],
            'revoked' => false,
        ]);

        $settings->ai_session_client_id = (string) $client->getKey();
        $settings->save();

        $this->info(sprintf('ai-session-Client angelegt. Client-ID: %s', $client->getKey()));
        $this->line('Diese ID braucht artwork-ai fuer das Tenant-Onboarding (aud-Pruefung).');

        return self::SUCCESS;
    }

    private function existingClient(AiAssistantSettings $settings): ?Client
    {
        if ($settings->ai_session_client_id === null) {
            return null;
        }

        return Passport::client()->newQuery()
            ->where('revoked', false)
            ->find($settings->ai_session_client_id);
    }
}

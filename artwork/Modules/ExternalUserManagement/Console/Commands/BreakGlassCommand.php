<?php

namespace Artwork\Modules\ExternalUserManagement\Console\Commands;

use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

/**
 * Break-Glass: Entkoppelt einen Account vom Identity Provider und stellt den
 * lokalen Passwort-Login wieder her. Setzt auth_provider auf 'local' zurück und
 * erzwingt einen Passwort-Reset (Reset-Link per Mail). Gedacht für den Notfall,
 * z. B. wenn der IdP ausfällt und niemand mehr administrieren kann.
 */
class BreakGlassCommand extends Command
{
    protected $signature = 'auth:break-glass {user : User ID or email address}
        {--no-reset : Nur entkoppeln, keinen Passwort-Reset-Link versenden}';

    protected $description = 'Detach an account from its identity provider and restore local password login';

    public function handle(): int
    {
        $identifier = (string) $this->argument('user');

        $user = User::query()
            ->when(
                is_numeric($identifier),
                fn ($query) => $query->where('id', (int) $identifier),
                fn ($query) => $query->where('email', $identifier)
            )
            ->first();

        if ($user === null) {
            $this->error("No user found for \"{$identifier}\".");

            return Command::FAILURE;
        }

        if (!$user->isIdpBound()) {
            $this->warn("User {$user->email} is already local-authenticated. Nothing to do.");

            return Command::SUCCESS;
        }

        $previousProvider = $user->auth_provider;

        $user->forceFill([
            'auth_provider' => 'local',
            'auth_provider_id' => null,
            'auth_provider_issuer' => null,
            'ad_managed' => false,
            'ad_identifier' => null,
        ])->save();

        $this->info("Decoupled {$user->email} from identity provider '{$previousProvider}'.");

        if ($this->option('no-reset')) {
            $this->warn('Skipped password reset link (--no-reset). Set a password manually before login.');

            return Command::SUCCESS;
        }

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->info("Password reset link sent to {$user->email}.");

            return Command::SUCCESS;
        }

        $this->error("Could not send password reset link ({$status}). Reset the password manually.");

        return Command::FAILURE;
    }
}

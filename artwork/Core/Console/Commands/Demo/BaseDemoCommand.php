<?php

declare(strict_types=1);

namespace Artwork\Core\Console\Commands\Demo;

use Artwork\Modules\User\Models\User;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

abstract class BaseDemoCommand extends Command
{
    /**
     * Demo-Daten legen Konten mit dem bekannten Passwort DemoDataPools::DEMO_PASSWORD an –
     * in Produktion ist das ein Sicherheitsrisiko, daher harter Abbruch.
     */
    protected function abortInProduction(): bool
    {
        if (!$this->getLaravel()->isProduction()) {
            return false;
        }

        $this->error(sprintf(
            '%s ist in Produktion (APP_ENV=production) gesperrt: '
            . 'Demo-Konten mit bekanntem Passwort dürfen dort nicht angelegt werden.',
            $this->getName()
        ));

        return true;
    }

    /**
     * Seeding läuft als authentifizierter User: Spatie-Activity-Logs bekommen
     * dadurch einen Verursacher (sonst entstehen causer-lose Einträge, die im
     * Projekt-Verlauf leer wirken), und Auth::id()-Defaults greifen sinnvoll.
     */
    protected function actAsSeedUser(): void
    {
        $user = User::query()->where('email', DemoDataPools::email('Katrin', 'Vollmer'))->first()
            ?? User::query()->orderBy('id')->first();
        if ($user !== null) {
            Auth::setUser($user);
        }
    }
}

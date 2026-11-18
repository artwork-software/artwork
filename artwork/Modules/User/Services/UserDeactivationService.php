<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\AppApi\Services\AppTokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deaktivieren statt Löschen: Rechte, Einstellungen und Zuordnungen (Projektteams, Schichten …) bleiben
 * erhalten, nur der Zugang ruht. Die Anmeldung sperrt App\Auth\ActiveUserProvider auf allen Guards.
 * App-Tokens und der „Angemeldet bleiben“-Cookie werden beim Deaktivieren entwertet, damit ein
 * verlorenes Gerät nach einer Reaktivierung nicht wieder Zugang hat. API-Schlüssel (Maschinen-Tokens)
 * bleiben bestehen und greifen nach einer Reaktivierung wieder.
 */
class UserDeactivationService
{
    public function __construct(private readonly AppTokenService $appTokenService)
    {
    }

    public function deactivate(User $user, User $actor): void
    {
        if ($user->isDeactivated()) {
            return;
        }

        // save() entfernt den Account über shouldBeSearchable() auch aus dem Suchindex.
        $user->forceFill([
            'deactivated_at' => now(),
            'deactivated_by' => $actor->getKey(),
            'remember_token' => Str::random(60),
        ])->save();

        $this->appTokenService->revokeAll($user);

        // Offene Browser-Sitzungen sofort beenden – nach einer Reaktivierung meldet man sich neu an.
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }
    }

    public function reactivate(User $user): void
    {
        if (!$user->isDeactivated()) {
            return;
        }

        $user->forceFill([
            'deactivated_at' => null,
            'deactivated_by' => null,
        ])->save();
    }
}

<?php

namespace Artwork\Modules\System\ApiManagement\Support;

use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;

/**
 * Passport kennt nur eine Scope-Liste. Der Scope der App-API steht dort mit drin, damit
 * CheckToken ihn prüfen kann, gehört aber nicht zu den Maschinen-Schlüsseln der
 * Schnittstellen-Seite — weder als Auswahl noch als Token in der Liste.
 */
final class MachineScopes
{
    public const APP_SCOPE = 'app';

    /** @return Collection<int, Scope> */
    public static function all(): Collection
    {
        return Passport::scopes()->reject(
            static fn (Scope $scope): bool => $scope->id === self::APP_SCOPE,
        )->values();
    }

    /** @return list<string> */
    public static function ids(): array
    {
        return self::all()->pluck('id')->all();
    }
}

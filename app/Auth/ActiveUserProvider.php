<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * User-Provider aller Guards (web/Session, api/Passport): deaktivierte Accounts gelten als unbekannt.
 * Laufende Sitzungen, App-Tokens, API-Schlüssel und Broadcast-Auth enden damit beim nächsten Request,
 * und nach einer Reaktivierung greifen sie wieder. Passwort-Reset findet deaktivierte Konten nicht.
 */
class ActiveUserProvider extends EloquentUserProvider
{
    /**
     * @param Model|null $model
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- Signatur der Elternklasse ist untypisiert
    protected function newModelQuery($model = null): Builder
    {
        $query = parent::newModelQuery($model);

        return $query->whereNull($query->qualifyColumn('deactivated_at'));
    }
}

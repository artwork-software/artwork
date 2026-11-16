<?php

namespace Artwork\Modules\User\Http\Resources;

/**
 * Treffer der Personensuche (users.search): gleiche Form wie UserIndexResource, aber ohne Schichtliste.
 * Kein Aufrufer der Suche zeigt Schichten an.
 */
class UserSearchResultResource extends UserIndexResource
{
    protected function includesShifts(): bool
    {
        return false;
    }
}

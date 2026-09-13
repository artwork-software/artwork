<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * Nebenbefund aus core-passport-verifikation (TokenGuard.php:146).
 *
 * Der Guard verwirft ein Token, wenn sub === aud ist UND der ausstellende Client den Grant
 * client_credentials fuehrt. Bei Integer-Client-IDs kollidiert die Client-ID mit User-IDs: Der
 * Personal-Access-Client hat ueblicherweise die ID 1, ist confidential (Secret gesetzt) und
 * first-party — damit trug er client_credentials und der User mit der ID 1 konnte keinen
 * Personal-Access-Token benutzen (401, ohne Meldung).
 *
 * Zielzustand ist das, was Passport selbst fuer einen frischen Personal-Access-Client anlegt:
 * grant_types = ['personal_access'], kein Secret (ClientRepository::createPersonalAccessGrantClient).
 * Der personal_access-Grant prueft kein Client-Secret, ein Secret hat dort also keine Funktion.
 *
 * Muss nach 2026_09_14_100000 laufen: vorher leitet Client::grantTypes() die Liste aus dem Secret
 * ab, und ein genulltes Secret wuerde personal_access aus der Ableitung entfernen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('oauth_clients', 'grant_types')) {
            return;
        }

        foreach (Passport::client()->newQuery()->cursor() as $client) {
            /** @var Client $client */
            if (!$client->hasGrantType('personal_access')) {
                continue;
            }

            $grantTypes = array_values(array_diff($client->grant_types, ['client_credentials']));
            $attributes = $client->getAttributes();

            if ($grantTypes === $client->grant_types && ($attributes['secret'] ?? null) === null) {
                continue;
            }

            Model::withoutTimestamps(fn (): bool => $client->forceFill([
                'grant_types' => $grantTypes,
                'secret' => null,
            ])->save());
        }
    }

    /**
     * Kein Rueckweg: Das urspruengliche Secret ist gehasht und nicht rekonstruierbar, und
     * client_credentials zurueckzuschreiben hiesse, den Fehler wiederherzustellen.
     */
    public function down(): void
    {
    }
};

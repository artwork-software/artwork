<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * Passport 12 -> 13: oauth_clients auf das neue Schema heben (core-passport-schema-upgrade).
 *
 * Bestandsinstanzen haben die alten Spalten, weil die Create-Migration unter dem alten Dateinamen
 * laengst als gelaufen vermerkt ist und ihre neue Definition daher nie greift. Frische
 * Installationen bringen das neue Schema bereits mit — dort ist diese Migration ein No-op.
 *
 * Inhalt uebernommen aus vendor/laravel/passport/UPGRADE.md, Abschnitt "OAuth Client Table Changes".
 * Die neuen Spalten werden bewusst ueber Passports eigene Kompatibilitaets-Accessoren befuellt
 * (Client::grantTypes(), Client::redirectUris()), nicht ueber ein selbst gebautes Mapping.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Merkmal des neuen Schemas ist owner_type, nicht grant_types: 2026_10_12_100000 ergaenzt
        // grant_types auch im Alt-Schema. Laeuft jene vor dieser (Release-Reihenfolge), sind die
        // dort gesetzten grant_types bereits korrekt und werden unten nur uebernommen.
        if (Schema::hasColumn('oauth_clients', 'owner_type')) {
            return;
        }

        $hasGrantTypes = Schema::hasColumn('oauth_clients', 'grant_types');

        Schema::table('oauth_clients', function (Blueprint $table) use ($hasGrantTypes): void {
            $table->nullableMorphs('owner', null, 'user_id');

            $table->after('provider', function (Blueprint $table) use ($hasGrantTypes): void {
                $table->text('redirect_uris')->nullable();

                if (!$hasGrantTypes) {
                    $table->text('grant_types')->nullable();
                }
            });
        });

        $defaultProvider = config('auth.guards.api.provider');

        foreach (Passport::client()->newQuery()->cursor() as $client) {
            /** @var Client $client */
            Model::withoutTimestamps(fn (): bool => $client->forceFill([
                'owner_id' => $client->user_id,
                'owner_type' => $client->user_id
                    ? config('auth.providers.' . ($client->provider ?: $defaultProvider) . '.model')
                    : null,
                'redirect_uris' => $client->redirect_uris,
                'grant_types' => $client->grant_types,
            ])->save());
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropColumn(['user_id', 'redirect', 'personal_access_client', 'password_client']);

            $table->text('redirect_uris')->nullable(false)->change();
            $table->text('grant_types')->nullable(false)->change();
        });
    }

    /**
     * Bewusst kein Rueckweg: Die alten Spalten liessen sich zwar wieder anlegen, die Zuordnung
     * grant_types -> personal_access_client/password_client ist aber nicht verlustfrei umkehrbar
     * (authorization_code, implicit und device_code haben dort keine Entsprechung). Ein Rollback
     * auf einer Kundeninstanz waere ein Datenverlust mit stillem Ausgang.
     */
    public function down(): void
    {
    }
};

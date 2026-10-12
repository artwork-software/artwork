<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ergänzt das Legacy-Schema von oauth_clients um die grant_types-Spalte aus Passport 13.
     *
     * Ohne die Spalte leitet Passport die Grant Types aus den alten Flags ab und gibt dabei jedem
     * vertraulichen First-Party-Client auch client_credentials. Der TokenGuard verwirft Tokens, deren
     * User-ID der Client-ID entspricht, sobald der Client client_credentials kann. Weil der Personal
     * Access Client meist ID 1 hat, war damit jeder Token von User 1 unbrauchbar (401).
     *
     * Personal-Access- und Password-Client bekommen deshalb feste Grant Types. Alle übrigen Clients
     * (z. B. die client_credentials-Clients der Ticketing-Anbindung) behalten NULL und damit die
     * bisherige Ableitung. Neue Clients schreibt Passport ab jetzt mit grant_types und ohne die alten
     * Flags, die deshalb einen Default brauchen.
     */
    public function up(): void
    {
        if (!$this->hasLegacySchema() || Schema::hasColumn('oauth_clients', 'grant_types')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->text('grant_types')->nullable()->after('redirect');
            $table->boolean('personal_access_client')->default(false)->change();
            $table->boolean('password_client')->default(false)->change();
        });

        DB::table('oauth_clients')
            ->where(fn ($query) => $query->where('personal_access_client', true)->orWhere('password_client', true))
            ->orderBy('id')
            ->get()
            ->each(function (object $client): void {
                $grantTypes = [];

                // Ein Personal Access Client ohne Secret galt für Passport schon bisher nicht als solcher.
                if ($client->personal_access_client && $client->secret !== null && $client->secret !== '') {
                    $grantTypes[] = 'personal_access';
                }

                if ($client->password_client) {
                    array_push($grantTypes, 'password', 'refresh_token');
                }

                if ($grantTypes !== []) {
                    DB::table('oauth_clients')
                        ->where('id', $client->id)
                        ->update(['grant_types' => json_encode($grantTypes)]);
                }
            });
    }

    public function down(): void
    {
        if (!$this->hasLegacySchema() || !Schema::hasColumn('oauth_clients', 'grant_types')) {
            return;
        }

        // Seit up() angelegte Clients tragen nur grant_types; die Flags daraus nachziehen, bevor die
        // Spalte wegfällt.
        DB::table('oauth_clients')->whereNotNull('grant_types')->orderBy('id')->get()
            ->each(function (object $client): void {
                $grantTypes = json_decode((string) $client->grant_types, true) ?: [];

                DB::table('oauth_clients')->where('id', $client->id)->update([
                    'personal_access_client' => in_array('personal_access', $grantTypes, true),
                    'password_client' => in_array('password', $grantTypes, true),
                ]);
            });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropColumn('grant_types');
            $table->boolean('personal_access_client')->default(null)->change();
            $table->boolean('password_client')->default(null)->change();
        });
    }

    /**
     * Nur Installationen mit dem alten Passport-Schema (Flags statt grant_types) sind betroffen.
     */
    private function hasLegacySchema(): bool
    {
        return Schema::hasColumn('oauth_clients', 'personal_access_client')
            && Schema::hasColumn('oauth_clients', 'password_client');
    }

    /**
     * Gleiche Verbindung wie Passports eigene Migrationen.
     */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};

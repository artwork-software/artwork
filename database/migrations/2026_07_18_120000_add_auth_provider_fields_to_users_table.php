<?php

use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hasColumn-Guard: schlägt der Backfill unten fehl, ist die Migration nicht
        // als gelaufen markiert — ein Re-Run darf dann nicht an "Duplicate column"
        // sterben (bekannte Squash-Lücken-Klasse).
        if (!Schema::hasColumn('users', 'auth_provider')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('auth_provider')->default('local')->after('password');
                $table->string('auth_provider_id')->nullable()->after('auth_provider');
                $table->string('auth_provider_issuer')->nullable()->after('auth_provider_id');
            });

            // Präfix-Index statt Volltext-Index: 3 × varchar(255) utf8mb4 (3060 Bytes)
            // passt nur mit Row-Format DYNAMIC — mit Präfixen (144 Zeichen = 576 Bytes)
            // funktioniert der Index auch auf COMPACT/älteren Instanzen.
            DB::statement(
                'ALTER TABLE users ADD INDEX users_auth_provider_index '
                . '(auth_provider(16), auth_provider_id(64), auth_provider_issuer(64))'
            );
        }

        $this->backfillFromExternalIdentities();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'auth_provider')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_auth_provider_index');
                $table->dropColumn(['auth_provider', 'auth_provider_id', 'auth_provider_issuer']);
            });
        }
    }

    /**
     * Übernimmt die Bestandsdaten der bisherigen ad_managed-/ExternalUser-Verknüpfung
     * in das neue kanonische auth_provider-Modell. Kein Purge, rein additiv.
     *
     * Nur lebendige Verknüpfungen aktiver Quellen: Nutzer mit gelöschter Verknüpfung
     * oder gelöschter/deaktivierter Quelle bleiben 'local' — sonst wäre ihr
     * Passwort-Login gesperrt, obwohl kein funktionierender SSO-Weg mehr existiert.
     */
    private function backfillFromExternalIdentities(): void
    {
        // Quellen einmalig laden – config ist encrypted:array, daher über das Model.
        $sources = ExternalUserSource::query()->where('active', true)->get()->keyBy('id');

        ExternalUser::query()
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(500, function ($externalUsers) use ($sources): void {
                foreach ($externalUsers as $externalUser) {
                    $source = $sources->get($externalUser->source_id);

                    if ($source === null) {
                        continue;
                    }

                    $provider = $source->type === 'identity_provider' ? 'oidc' : 'ldap';
                    $identifier = $externalUser->identification;

                    if ($identifier === null || $identifier === '') {
                        continue;
                    }

                    try {
                        $issuer = $this->deriveIssuer($source);
                    } catch (\Throwable) {
                        // Korrupte/nicht entschlüsselbare config einer Quelle darf
                        // den Backfill (und damit das Deploy) nicht abbrechen.
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $externalUser->user_id)
                        ->where('auth_provider', 'local')
                        ->update([
                            'auth_provider' => $provider,
                            'auth_provider_id' => $identifier,
                            'auth_provider_issuer' => $issuer,
                        ]);
                }
            });
    }

    private function deriveIssuer(ExternalUserSource $source): ?string
    {
        $config = $source->config ?? [];

        if ($source->type === 'ldap') {
            return $config['host'] ?? null;
        }

        if (!empty($config['issuer'])) {
            return $config['issuer'];
        }

        if (!empty($config['discovery_url'])) {
            return preg_replace('#/\.well-known/openid-configuration/?$#', '', (string) $config['discovery_url']);
        }

        return null;
    }
};

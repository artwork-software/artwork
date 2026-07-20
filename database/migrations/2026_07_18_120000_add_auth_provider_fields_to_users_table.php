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
        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_provider')->default('local')->after('password');
            $table->string('auth_provider_id')->nullable()->after('auth_provider');
            $table->string('auth_provider_issuer')->nullable()->after('auth_provider_id');
            $table->index(
                ['auth_provider', 'auth_provider_id', 'auth_provider_issuer'],
                'users_auth_provider_index'
            );
        });

        $this->backfillFromExternalIdentities();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_auth_provider_index');
            $table->dropColumn(['auth_provider', 'auth_provider_id', 'auth_provider_issuer']);
        });
    }

    /**
     * Übernimmt die Bestandsdaten der bisherigen ad_managed-/ExternalUser-Verknüpfung
     * in das neue kanonische auth_provider-Modell. Kein Purge, rein additiv.
     */
    private function backfillFromExternalIdentities(): void
    {
        // Quellen einmalig laden – config ist encrypted:array, daher über das Model.
        $sources = ExternalUserSource::withTrashed()->get()->keyBy('id');

        ExternalUser::withTrashed()
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

                    DB::table('users')
                        ->where('id', $externalUser->user_id)
                        ->where('auth_provider', 'local')
                        ->update([
                            'auth_provider' => $provider,
                            'auth_provider_id' => $identifier,
                            'auth_provider_issuer' => $this->deriveIssuer($source),
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

<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Neue OIDC-Konten brauchen jetzt email_verified oder den Schalter „E-Mail-Adressen vertrauen“.
     * Bestehende Quellen bekommen den Schalter, damit sich dort nichts ändert (Entra liefert den
     * Claim in der Regel nicht). Die Config liegt als 'encrypted:array' vor.
     */
    public function up(): void
    {
        $this->setTrustFlag(true);
    }

    public function down(): void
    {
        $this->setTrustFlag(null);
    }

    private function setTrustFlag(?bool $value): void
    {
        if (!Schema::hasTable('external_user_sources')) {
            return;
        }

        DB::table('external_user_sources')
            ->where('type', 'identity_provider')
            ->select(['id', 'config'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($value): void {
                foreach ($rows as $row) {
                    if ($row->config === null || $row->config === '') {
                        continue;
                    }

                    try {
                        $config = json_decode(Crypt::decryptString($row->config), true);
                    } catch (DecryptException) {
                        continue;
                    }

                    if (!is_array($config)) {
                        continue;
                    }

                    if ($value === null) {
                        unset($config['trust_idp_email']);
                    } else {
                        $config['trust_idp_email'] = $value;
                    }

                    DB::table('external_user_sources')
                        ->where('id', $row->id)
                        ->update(['config' => Crypt::encryptString(json_encode($config))]);
                }
            });
    }
};

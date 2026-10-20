<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Das Sage-Passwort lag im Klartext in der Datenbank; das Model speichert es jetzt verschlüsselt
     * ('encrypted'-Cast). Bestehende Werte werden einmalig verschlüsselt, bereits verschlüsselte übersprungen.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sage_api_settings')) {
            return;
        }

        DB::table('sage_api_settings')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    if ($row->password === null || $row->password === '') {
                        continue;
                    }

                    try {
                        Crypt::decryptString($row->password);
                    } catch (DecryptException) {
                        DB::table('sage_api_settings')
                            ->where('id', $row->id)
                            ->update(['password' => Crypt::encryptString($row->password)]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sage_api_settings')) {
            return;
        }

        DB::table('sage_api_settings')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    try {
                        $plain = Crypt::decryptString((string) $row->password);
                    } catch (DecryptException) {
                        continue;
                    }

                    DB::table('sage_api_settings')->where('id', $row->id)->update(['password' => $plain]);
                }
            });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Schalter "Dateiupload für Externe erlauben" (Tool-Einstellungen → Datei-Einstellungen).
 * Backfill für bestehende Installationen: AUS.
 */
return new class extends Migration {
    public function up(): void
    {
        $exists = DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'external_file_upload_enabled')
            ->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'group' => 'general',
                'name' => 'external_file_upload_enabled',
                'locked' => false,
                'payload' => 'false',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'external_file_upload_enabled')
            ->delete();
    }
};

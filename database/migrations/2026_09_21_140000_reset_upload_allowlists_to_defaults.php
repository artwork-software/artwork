<?php

use Artwork\Core\FileHandling\Upload\UploadSettingDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Setzt die Endungs-Allowlists der Datei-Einstellungen (Projekt, Raum, Branding, Vertrag) einmalig auf die
 * Standardwerte; Größenlimits und alle übrigen Einstellungen bleiben unverändert.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        foreach (UploadSettingDefaults::mimeTypes() as $name => $extensions) {
            $payload = json_encode(array_values($extensions));

            $query = DB::table('settings')
                ->where('group', UploadSettingDefaults::SETTINGS_GROUP)
                ->where('name', $name);

            if ($query->exists()) {
                $query->where('payload', '!=', $payload)->update([
                    'payload' => $payload,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('settings')->insert([
                'group' => UploadSettingDefaults::SETTINGS_GROUP,
                'name' => $name,
                'locked' => false,
                'payload' => $payload,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};

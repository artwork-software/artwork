<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    private const OLD_GROUP = 'general';
    private const OLD_NAME = 'external_file_upload_enabled';

    public function up(): void
    {
        // Schalter "Dateiupload für Externe erlauben" gehört zu den Einstellungen des externen Zugriffs.
        // Ein eventuell schon unter "general" gespeicherter Wert wird übernommen, die alte Zeile entfernt.
        $old = DB::table('settings')
            ->where('group', self::OLD_GROUP)
            ->where('name', self::OLD_NAME)
            ->value('payload');

        $value = $old !== null ? (bool) json_decode((string) $old, true) : false;

        if (!$this->migrator->exists('external_access.file_upload_enabled')) {
            $this->migrator->add('external_access.file_upload_enabled', $value);
        }

        DB::table('settings')
            ->where('group', self::OLD_GROUP)
            ->where('name', self::OLD_NAME)
            ->delete();
    }

    public function down(): void
    {
        $this->migrator->delete('external_access.file_upload_enabled');
    }
};

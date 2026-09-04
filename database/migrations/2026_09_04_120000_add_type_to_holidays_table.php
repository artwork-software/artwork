<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feiertagstyp als eigene Spalte: gesetzlicher Feiertag (public), Schulferien (school),
 * eigener Eintrag (custom). Bisher war der Typ nur indirekt über from_api + Namensendung
 * "ferien" erkennbar. Backfill nach genau dieser Heuristik, danach setzt der Import den Typ
 * aus der OpenHolidays-API ("Public" / "School") und manuelle Einträge wählen ihn im Formular.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        if (!Schema::hasColumn('holidays', 'type')) {
            Schema::table('holidays', static function (Blueprint $table): void {
                $table->string('type', 16)->default('custom')->after('from_api');
            });
        }

        // Backfill: importierte Ferien -> school, übrige importierte -> public, manuelle -> custom.
        DB::table('holidays')
            ->where('from_api', true)
            ->where('name', 'like', '%ferien')
            ->update(['type' => 'school']);

        DB::table('holidays')
            ->where('from_api', true)
            ->where('name', 'not like', '%ferien')
            ->update(['type' => 'public']);

        DB::table('holidays')
            ->where('from_api', false)
            ->update(['type' => 'custom']);
    }

    public function down(): void
    {
        if (Schema::hasTable('holidays') && Schema::hasColumn('holidays', 'type')) {
            Schema::table('holidays', static function (Blueprint $table): void {
                $table->dropColumn('type');
            });
        }
    }
};

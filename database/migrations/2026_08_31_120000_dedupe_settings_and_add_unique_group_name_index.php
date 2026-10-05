<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Der Migration-Squash-Schema-Dump hat den Unique-Index auf settings(group, name)
 * verloren (Spatie liefert ihn in seiner create_settings_table-Migration mit).
 * Spatie v3 speichert Settings per upsert(..., ['group', 'name'], ['payload']) —
 * ohne Unique-Index matcht MySQLs ON DUPLICATE KEY nie, sodass JEDES Speichern
 * einer Settings-Gruppe neue Rows für alle Properties der Gruppe einfügt.
 *
 * Hier: pro (group, name) nur die neueste Row (höchste id) behalten — das ist
 * auch der Wert, den Gruppen-Reads effektiv geliefert haben — und danach den
 * Unique-Index anlegen, damit upsert wieder aktualisiert statt einzufügen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::statement(
            'DELETE older FROM settings older
                JOIN settings newer
                  ON newer.`group` = older.`group`
                 AND newer.`name` = older.`name`
                 AND newer.id > older.id'
        );

        if (!Schema::hasIndex('settings', ['group', 'name'], 'unique')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->unique(['group', 'name']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasIndex('settings', ['group', 'name'], 'unique')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->dropUnique(['group', 'name']);
            });
        }
    }
};

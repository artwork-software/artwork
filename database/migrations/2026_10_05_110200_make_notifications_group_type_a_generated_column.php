<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notifications.groupType war nie befüllt; Zähler und Listen der Meldungen-Seite lasen die Gruppe
 * per JSON_EXTRACT aus data – je Zeile, über alle (auch archivierten) Meldungen einer Person.
 * Als gespeicherte generierte Spalte ist die Gruppe ohne Änderung an den Schreibwegen immer
 * aktuell und über den Index (Empfänger, Gruppe, gelesen) direkt abfragbar.
 * JSON_VALID-Guard: Im Strict-Mode bricht JSON_EXTRACT auf einer einzigen ungültigen Zeile den
 * ganzen ALTER ab (MariaDB-Fehler 4038) – solche Zeilen bekommen stattdessen groupType = null.
 */
return new class extends Migration
{
    private const INDEX = 'notifications_notifiable_group_read_index';

    public function up(): void
    {
        // Wiederholbar nach einem Teil-Fehlschlag: MariaDB entfernt beim Spalten-Drop nur die Spalte aus
        // dem Index, der Index-Name bliebe belegt.
        if (Schema::hasIndex('notifications', self::INDEX)) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX);
            });
        }

        if (Schema::hasColumn('notifications', 'groupType')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropColumn('groupType');
            });
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('groupType', 100)
                ->nullable()
                ->after('type')
                ->storedAs("IF(JSON_VALID(`data`), JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.groupType')), NULL)");
            $table->index(['notifiable_type', 'notifiable_id', 'groupType', 'read_at'], self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX);
            $table->dropColumn('groupType');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('groupType')->nullable()->after('type');
        });
    }
};

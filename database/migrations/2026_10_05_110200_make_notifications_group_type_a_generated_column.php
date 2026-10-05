<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notifications.groupType war nie befüllt; Zähler und Listen der Meldungen-Seite lasen die Gruppe
 * per JSON_EXTRACT aus data – je Zeile, über alle (auch archivierten) Meldungen einer Person.
 * Als gespeicherte generierte Spalte ist die Gruppe ohne Änderung an den Schreibwegen immer
 * aktuell und über den Index (Empfänger, Gruppe, gelesen) direkt abfragbar.
 */
return new class extends Migration
{
    private const INDEX = 'notifications_notifiable_group_read_index';

    public function up(): void
    {
        if (Schema::hasColumn('notifications', 'groupType')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropColumn('groupType');
            });
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('groupType', 100)
                ->nullable()
                ->after('type')
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.groupType'))");
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

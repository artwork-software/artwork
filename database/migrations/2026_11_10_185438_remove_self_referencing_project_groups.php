<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Projektgruppen, die sich selbst als Mitglied führen, ließen den Aufbau der Kalenderdaten endlos laufen.
     * Die Schreibwege verhindern den Selbstbezug jetzt; hier werden vorhandene Einträge entfernt.
     */
    public function up(): void
    {
        DB::table('project_groups')->whereColumn('project_id', 'group_id')->delete();
    }

    public function down(): void
    {
        // Die entfernten Einträge waren fehlerhaft und werden nicht wiederhergestellt.
    }
};

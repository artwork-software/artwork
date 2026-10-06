<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Raumanfrage-Typen lagen in der jeweils falschen Gruppe: neue Anfragen an Raumadmins (ROOM_REQUEST)
 * unter „Termine“, Antworten an Anfragende (UPSERT_ROOM_REQUEST) unter „Räume“. Bestehende Einträge
 * ziehen mit, damit sie in derselben Sektion stehen wie neue (groupType ist eine generierte Spalte).
 * Die Einstellungszeilen übernimmt artwork:update (syncTypeMetadata).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->move('ROOM_REQUEST', 'ROOMS');
        $this->move('NOTIFICATION_UPSERT_ROOM_REQUEST', 'EVENTS');
    }

    public function down(): void
    {
        $this->move('ROOM_REQUEST', 'EVENTS');
        $this->move('NOTIFICATION_UPSERT_ROOM_REQUEST', 'ROOMS');
    }

    private function move(string $type, string $group): void
    {
        // JSON_VALID zuerst: im Strict-Mode bricht JSON_EXTRACT sonst an einer ungültigen Zeile ab
        DB::table('notifications')
            ->whereRaw('JSON_VALID(data)')
            ->where('data->type', $type)
            ->update(['data' => DB::raw("JSON_SET(data, '$.groupType', '" . $group . "')")]);
    }
};

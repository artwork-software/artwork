<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gegenstück zu 2026_10_06_000100 für die Einstellungszeilen: die Gruppen-Schalter („Räume aus“)
 * filtern auf notification_settings.group_type. Bisher zog das erst artwork:update nach – schlägt das
 * im Deploy fehl (container-update || true), schaltete der Gruppen-Schalter bis dahin den falschen Typ.
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
        DB::table('notification_settings')
            ->where('type', $type)
            ->update(['group_type' => $group]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Für welche Spielstätte die Preisklassen eines Termins gesetzt sind; wechselt der Raum, folgen sie dem neuen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketing_event_releases', function (Blueprint $table): void {
            $table->uuid('venue_id')->nullable()->after('classes');
        });

        DB::table('ticketing_event_releases')
            ->join('events', 'events.id', '=', 'ticketing_event_releases.event_id')
            ->join('ticketing_room_links', 'ticketing_room_links.room_id', '=', 'events.room_id')
            ->update(['ticketing_event_releases.venue_id' => DB::raw('ticketing_room_links.venue_id')]);
    }

    public function down(): void
    {
        Schema::table('ticketing_event_releases', function (Blueprint $table): void {
            $table->dropColumn('venue_id');
        });
    }
};

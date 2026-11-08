<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seit wann das Ticket-Haus wirklich verkauft; null = Testmodus, den nur der Assistent ausdrücklich
     * wählt. Bestehende Verbindungen verkaufen schon und gelten ab ihrer Verbindung als live.
     */
    public function up(): void
    {
        Schema::table('ticketing_connections', function (Blueprint $table): void {
            $table->timestamp('live_at')->nullable()->useCurrent()->after('connected_by_user_id');
        });

        DB::table('ticketing_connections')->update(['live_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('ticketing_connections', function (Blueprint $table): void {
            $table->dropColumn('live_at');
        });
    }
};

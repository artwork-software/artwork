<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warum der letzte Abgleich nach einer Kalenderänderung in tickets scheiterte; leer heißt auf Stand.
        Schema::table('ticketing_event_releases', function (Blueprint $table): void {
            $table->text('sync_error')->nullable()->after('released_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticketing_event_releases', function (Blueprint $table): void {
            $table->dropColumn('sync_error');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zuletzt im Künstlerplan gewählte Terminarten je Nutzer:in, damit sie beim nächsten Export
 * wieder vorausgewählt sind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('artist_plan_event_type_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('artist_plan_event_type_ids');
        });
    }
};

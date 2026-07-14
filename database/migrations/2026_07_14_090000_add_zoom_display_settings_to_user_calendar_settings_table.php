<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_calendar_settings', 'calendar_column_width')) {
                // Globale Raumspaltenbreite (px) — vom Zoom entkoppelt
                $table->unsignedSmallInteger('calendar_column_width')->default(212)->after('expand_days');
            }
            if (!Schema::hasColumn('user_calendar_settings', 'show_artist_names_as_title')) {
                // Kompaktkachel: Künstler:innen-Namen statt Termintitel anzeigen
                $table->boolean('show_artist_names_as_title')->default(false)->after('calendar_column_width');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_calendar_settings', 'calendar_column_width')) {
                $table->dropColumn('calendar_column_width');
            }
            if (Schema::hasColumn('user_calendar_settings', 'show_artist_names_as_title')) {
                $table->dropColumn('show_artist_names_as_title');
            }
        });
    }
};

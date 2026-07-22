<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_calendar_settings', 'show_day_remarks')) {
                // Default true: Spalte ist nach Aktivierung des Features für alle
                // User zunächst sichtbar und kann (wenn nicht verpflichtend)
                // über die Anzeigeeinstellungen abgewählt werden.
                $table->boolean('show_day_remarks')->default(true)->after('show_artist_names_as_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_calendar_settings', 'show_day_remarks')) {
                $table->dropColumn('show_day_remarks');
            }
        });
    }
};

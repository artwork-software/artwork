<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Einlass: optionale Uhrzeit (ohne Datum) bezogen auf den Starttag
     * des Termins. Bewusst keine Validierung gegen start_time, damit
     * Sonderfälle wie "Einlass 23:30, Beginn 00:30" möglich bleiben.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (!Schema::hasColumn('events', 'admission_time')) {
                $table->time('admission_time')->nullable()->after('end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (Schema::hasColumn('events', 'admission_time')) {
                $table->dropColumn('admission_time');
            }
        });
    }
};

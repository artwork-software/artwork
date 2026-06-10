<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Überbuchungsplätze je Schicht-Funktion: value bleibt der geplante Bedarf,
     * overbooked_value zählt zusätzlich geschaffene Überbuchungsplätze (offen + befüllt).
     */
    public function up(): void
    {
        Schema::table('shifts_qualifications', function (Blueprint $table): void {
            $table->unsignedInteger('overbooked_value')->default(0)->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('shifts_qualifications', function (Blueprint $table): void {
            $table->dropColumn('overbooked_value');
        });
    }
};

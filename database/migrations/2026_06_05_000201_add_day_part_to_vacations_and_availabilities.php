<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * DP-18: Der Verfügbarkeitsstatus "Frei" (FREE_WORK) wird in
     * Ganzer freier Tag (full / null) und Halber freier Tag (morning|afternoon) unterteilt.
     */
    public function up(): void
    {
        foreach (['vacations', 'availabilities'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('day_part', 16)->nullable()->after('full_day');
            });
        }
    }

    public function down(): void
    {
        foreach (['vacations', 'availabilities'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('day_part');
            });
        }
    }
};

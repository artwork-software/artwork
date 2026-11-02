<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vorgaben je Unterkunft für Künstler*innenaufenthalte; null = allgemeine Vorgabe aus den Einstellungen.
     */
    public function up(): void
    {
        Schema::table('accommodations', function (Blueprint $table): void {
            $table->decimal('daily_allowance', 10, 2)->nullable()->after('note');
            $table->decimal('breakfast_deduction_per_day', 10, 2)->nullable()->after('daily_allowance');
        });
    }

    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table): void {
            $table->dropColumn(['daily_allowance', 'breakfast_deduction_per_day']);
        });
    }
};

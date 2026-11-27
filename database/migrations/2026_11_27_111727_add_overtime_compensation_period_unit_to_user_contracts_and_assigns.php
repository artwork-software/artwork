<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Einheit der Überstunden-Abbaufrist: "days" (Standard, auch bei null) oder "calendar_weeks"
     * (Frist endet am Sonntag der N-ten Kalenderwoche nach der Entstehungswoche).
     */
    public function up(): void
    {
        Schema::table('user_contracts', function (Blueprint $table): void {
            $table->string('overtime_compensation_period_unit', 20)
                ->nullable()
                ->after('overtime_compensation_period');
        });

        Schema::table('user_contract_assigns', function (Blueprint $table): void {
            $table->string('overtime_compensation_period_unit', 20)
                ->nullable()
                ->after('overtime_compensation_period');
        });
    }

    public function down(): void
    {
        Schema::table('user_contracts', function (Blueprint $table): void {
            $table->dropColumn('overtime_compensation_period_unit');
        });

        Schema::table('user_contract_assigns', function (Blueprint $table): void {
            $table->dropColumn('overtime_compensation_period_unit');
        });
    }
};

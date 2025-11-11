<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shift_rules', function (Blueprint $table) {
            $table->string('trigger_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_rules', function (Blueprint $table) {
            $table->enum('trigger_type', [
                'maxWorkingHoursOnDay',
                'maxConsecWorkingDays', 
                'weeklyMaxHours',
                'restTimeBeforeWorkday',
                'restTimeBeforeHoliday',
                'minDaysBeforeCommit'
            ])->change();
        });
    }
};

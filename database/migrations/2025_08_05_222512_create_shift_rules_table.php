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
        Schema::create('shift_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', [
                'maxWorkingHoursOnDay',
                'maxConsecWorkingDays', 
                'weeklyMaxHours',
                'restTimeBeforeWorkday',
                'restTimeBeforeHoliday',
                'minDaysBeforeCommit'
            ]);
            $table->decimal('individual_number_value', 8, 2);
            $table->string('warning_color', 7)->default('#ff0000');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_rules');
    }
};

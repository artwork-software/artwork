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
        Schema::create('user_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('free_full_days_per_week')->default(0);
            $table->unsignedInteger('free_half_days_per_week')->default(0);
            $table->boolean('special_day_rule_active')->default(false);
            $table->unsignedInteger('compensation_period')->default(0);
            $table->text('description')->nullable();
            $table->unsignedInteger('free_sundays_per_season')->default(0);
            $table->decimal('days_off_first_26_weeks', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_contracts');
    }
};

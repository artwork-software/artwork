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
        /**
         * 'user_id',
         * 'user_contract_id',
         * 'free_full_days_per_week',
         * 'free_half_days_per_week',
         * 'special_day_rule_active',
         * 'compensation_period',
         * 'free_sundays_per_season',
         * 'days_off_first_26_weeks'
         */
        Schema::create('user_contract_assigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('user_contract_id')
                ->nullable()
                ->constrained('user_contracts')
                ->onDelete('set null');
            $table->integer('free_full_days_per_week')->default(0);
            $table->integer('free_half_days_per_week')->default(0);
            $table->boolean('special_day_rule_active')->default(false);
            $table->integer('compensation_period')->default(0);
            $table->integer('free_sundays_per_season')->default(0);
            $table->float('days_off_first_26_weeks')->default(0.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_contract_assigns');
    }
};

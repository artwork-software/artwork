<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('main_positions', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('table_id');
            $table->enum('type', ['BUDGET_TYPE_COST', 'BUDGET_TYPE_EARNING']);
            $table->integer('position');
            $table->string('name')->nullable();
            $table->enum('is_verified', [
                'BUDGET_VERIFIED_TYPE_NOT_VERIFIED',
                'BUDGET_VERIFIED_TYPE_CLOSED',
                'BUDGET_VERIFIED_TYPE_REQUESTED'
            ])->default('BUDGET_VERIFIED_TYPE_NOT_VERIFIED');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('main_positions');
    }
};

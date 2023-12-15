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
        Schema::create('filters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id');
            $table->boolean('isLoud')->default(false)->nullable();
            $table->boolean('isNotLoud')->default(false)->nullable();
            $table->boolean('hasAudience')->default(false)->nullable();
            $table->boolean('hasNoAudience')->default(false)->nullable();
            $table->boolean('adjoiningNoAudience')->default(false)->nullable();
            $table->boolean('adjoiningNotLoud')->default(false)->nullable();
            $table->boolean('allDayFree')->default(false)->nullable();
            $table->boolean('showAdjoiningRooms')->default(false)->nullable();
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
        Schema::dropIfExists('filters');
    }
};

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
        Schema::create('shifts_service_providers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('service_provider_id');
            $table->boolean('is_master')->default(false);
            $table->unsignedBigInteger('shift_count');
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
        Schema::dropIfExists('shifts_service_providers');
    }
};

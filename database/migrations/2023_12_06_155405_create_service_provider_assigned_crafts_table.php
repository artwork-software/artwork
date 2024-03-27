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
        Schema::create('service_provider_assigned_crafts', function (Blueprint $table): void {
            $table->id();
            //cascade on delete so the assigned crafts are deleted when corresponding service_provider is deleted
            $table->foreignId('service_provider_id')->constrained('service_providers')->cascadeOnDelete();
            $table->foreignId('craft_id')->constrained('crafts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_assigned_crafts');
    }
};

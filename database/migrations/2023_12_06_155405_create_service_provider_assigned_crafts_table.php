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
        Schema::create('service_provider_assigned_crafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained('users');
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

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
        Schema::create('users_assigned_crafts', function (Blueprint $table): void {
            $table->id();
            //cascade on delete so the assigned crafts are deleted when corresponding user is deleted
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
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
        Schema::dropIfExists('users_assigned_crafts');
    }
};

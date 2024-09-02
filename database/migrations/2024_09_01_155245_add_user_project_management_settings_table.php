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
        Schema::create(
            'user_project_management_settings',
            function(Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->json('settings');
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_project_management_settings');
    }
};

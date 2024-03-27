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
        Schema::create('project_user', function (Blueprint $table): void {
            $table->id();
            $table->integer('project_id');
            $table->integer('user_id');
            $table->boolean('access_budget')->default(false);
            $table->boolean('is_manager')->default(false);
            $table->boolean('can_write')->default(false);
            $table->boolean('delete_permission')->default(false);
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
        Schema::dropIfExists('project_user');
    }
};

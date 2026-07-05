<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_project_role_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_role_id')->constrained('project_roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_project_role_user');
    }
};

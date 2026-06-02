<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_access_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_tab_id')->constrained()->cascadeOnDelete();
            $table->enum('access_type', ['read', 'write']);
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['external_access_id', 'valid_to']);
            $table->unique(['external_access_id', 'project_tab_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_access_scopes');
    }
};

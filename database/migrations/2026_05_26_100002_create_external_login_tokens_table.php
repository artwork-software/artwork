<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('expires_at');
            $table->index(['external_access_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_login_tokens');
    }
};

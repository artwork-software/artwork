<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('crm_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('crm_access_expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'revoked_at']);
            $table->index('crm_access_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_accesses');
    }
};

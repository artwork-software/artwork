<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('source', ['crm_index', 'project_tab']);
            $table->unsignedBigInteger('source_reference_id')->nullable();
            $table->timestamp('email_sent_at');
            $table->timestamp('first_redeemed_at')->nullable();
            $table->timestamps();

            $table->index('external_access_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_invitations');
    }
};

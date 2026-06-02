<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_pending_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
            $table->enum('context', ['crm_self']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'partially_approved', 'superseded']);
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['external_access_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_pending_submissions');
    }
};

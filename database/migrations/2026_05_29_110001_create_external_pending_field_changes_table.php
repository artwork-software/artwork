<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_pending_field_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')
                ->constrained('external_pending_submissions')
                ->cascadeOnDelete();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('field_key');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected']);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_pending_field_changes');
    }
};

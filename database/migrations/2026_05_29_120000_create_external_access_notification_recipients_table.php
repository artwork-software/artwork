<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_access_notification_recipients', function (Blueprint $table): void {
            $table->id();
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');
            $table->json('notification_types');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id']);
            $table->unique(['recipient_type', 'recipient_id'], 'external_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_access_notification_recipients');
    }
};

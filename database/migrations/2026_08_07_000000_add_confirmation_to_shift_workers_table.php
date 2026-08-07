<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_workers', function (Blueprint $table): void {
            // null = ausstehend, 'accepted' | 'declined'
            $table->string('confirmation_status', 20)
                ->nullable()
                ->after('assigned_by_user_id');
            $table->timestamp('confirmation_at')
                ->nullable()
                ->after('confirmation_status');
            // Wer geklickt hat: die Person selbst oder Planer:in (Proxy für Externe)
            $table->foreignId('confirmation_by_user_id')
                ->nullable()
                ->after('confirmation_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('confirmation_comment')
                ->nullable()
                ->after('confirmation_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('shift_workers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('confirmation_by_user_id');
            $table->dropColumn(['confirmation_status', 'confirmation_at', 'confirmation_comment']);
        });
    }
};

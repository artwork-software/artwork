<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * „Eingegebene Daten absenden“ sperrt den Tab für die externe Person, bis intern jemand bestätigt
 * oder zur Überarbeitung zurückgibt. Status + Prüfung stehen am Scope (pro Person und Tab).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_access_scopes', function (Blueprint $table): void {
            $table->string('submission_status', 20)->default('open')->after('last_submitted_at');
            $table->timestamp('reviewed_at')->nullable()->after('submission_status');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->text('review_comment')->nullable()->after('reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('external_access_scopes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn(['submission_status', 'reviewed_at', 'review_comment']);
        });
    }
};

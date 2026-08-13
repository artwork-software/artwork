<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_issues', function (Blueprint $table): void {
            // 'returned' | 'not_returned' — Rückmeldung auf die Rückgabe-Erinnerung
            $table->string('return_status')->nullable()->after('return_remarks');
            $table->timestamp('return_notification_sent_at')->nullable()->after('return_status');
        });
    }

    public function down(): void
    {
        Schema::table('external_issues', function (Blueprint $table): void {
            $table->dropColumn(['return_status', 'return_notification_sent_at']);
        });
    }
};

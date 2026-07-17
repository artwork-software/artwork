<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('external_users', function (Blueprint $table) {
            $table->timestamp('import_notification_sent_at')->nullable()->after('meta_data');
        });

        // Bereits importierte Nutzer als "benachrichtigt" markieren, damit die
        // Import-Mail nicht rückwirkend an bestehende Accounts versendet wird.
        DB::table('external_users')
            ->whereNull('import_notification_sent_at')
            ->update(['import_notification_sent_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('external_users', function (Blueprint $table) {
            $table->dropColumn('import_notification_sent_at');
        });
    }
};
